<?php

/**
 * Controller Class
 *
 */
class Controller {
	
	private $model;
	private $rows_per_page;
	private $csv;
	private $slug;
	private $url;
	
	/**
     * Constructor - sets table variables and slugs
     *
     */
	public function __construct() {
		
		// read settings
		$settings = parse_ini_file(FILE_INI);
		$this->rows_per_page = $settings['rows_per_page'];
		
		// csv settings
		$this->csv['file_name'] = $settings['csv_file_name'];
		$this->csv['encoding']  = $settings['csv_encoding'];
		
		// database
		$this->model = new Model($settings['table_name']);

		// slugs & menu
		$this->slug['list']     = $settings['base_slug'] . '_list';
		$this->slug['add']      = $settings['base_slug'] . '_add';
		$this->slug['edit']     = $settings['base_slug'] . '_edit';
		$this->slug['settings'] = $settings['base_slug'] . '_settings';
		
		add_action('init', array($this, 'export_csv'));
		add_action('admin_menu', array($this, 'add_menu'));

		$this->url['list']     = admin_url('admin.php?page=' . $this->slug['list']);
		$this->url['edit']     = admin_url('admin.php?page=' . $this->slug['edit']);
		$this->url['add']      = admin_url('admin.php?page=' . $this->slug['add']);
		$this->url['settings'] = admin_url('admin.php?page=' . $this->slug['settings']);
	}

	/**
     * Adds menus to left-hand sidebar
     *
     */
	public function add_menu() {
		add_menu_page('Simple Table Manager - List', 'Simple Table Manager', 'manage_options', $this->slug['list'], array($this, list_all));
		add_submenu_page(null, 'Simple Table Manager - Add New', 'Add New', 'manage_options', $this->slug['add'], array($this, add_new));
		add_submenu_page($this->slug['list'], 'Simple Table Manager - Settings', 'Settings', 'manage_options', $this->slug['settings'], array($this, settings));
		add_submenu_page(null, 'Simple Table Manager - Edit', 'Edit', 'manage_options', $this->slug['edit'], array($this, edit));
	}

	/**
     * Top menu - Lists all data from table
     * 
     */
	public function list_all() {
		
		// export csv via post
		$task_id = mt_rand();
		$_SESSION['export'] = $task_id;
		
		// key word search
		$key_word = "";
		if (isset($_POST['search']))	$key_word = $_POST['search'];
		if (isset($_GET['search']))		$key_word = $_GET['search'];
		
		$key_word = stripslashes_deep($key_word);
		
		// order by
		$order_by = "";
		$order = "";
		if (isset($_GET['orderby'])) {
			$order_by = $_GET['orderby'];
			$order = $_GET['order'];
		}
		
		// manage record quantity
		$begin_row = 0;
		if (isset($_GET['beginrow'])){	
			if (is_numeric($_GET['beginrow'])){
				$begin_row = $_GET['beginrow'];
			}
		}
		$total = $this->model->count_rows($key_word);	// count all data rows
		$next_begin_row = $begin_row + $this->rows_per_page;
		if ($total < $next_begin_row) $next_begin_row = $total;
		$last_begin_row = $this->rows_per_page * (floor(($total - 1) / $this->rows_per_page));
		
		// stuff to display
		$table_name = $this->model->get_table_name();
		$primary_key = $this->model->get_primary_key();
		$columns = $this->model->get_columns();
		$result = $this->model->select($key_word, $order_by, $order, $begin_row, $this->rows_per_page);
		
		include(FILE_VIEW_LIST);
	}
	
	
	/**
     * Add New data
     *
     */
	public function add_new() {
		
		$table_name = $this->model->get_table_name();
		$primary_key = $this->model->get_primary_key();
		$columns = $this->model->get_columns();
		
		include(FILE_VIEW_ADD);
	}
	
	
	/**
     * Export data to csv file
     *
     */
	public function export_csv() {
		if (isset($_GET['export'])) {
			
			// output contents
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=" . $this->csv['file_name']);
			
			// field names
			foreach ($this->model->get_columns() as $name => $type) {
				print($name . DELMITER);
			}
			print(NEW_LINE);
			
			// data
			foreach ($this->model->select_all() as $row ){
				foreach ($row as $k => $v ) {
					$str = preg_replace('/"/', '""', $v);
					print("\"" . mb_convert_encoding($str, $this->csv_encoding, 'UTF-8')."\"" . DELMITER);
		      	}
				print(NEW_LINE);
			}
			exit;
		}
	}
	
	/**
     * Edit data
     *
     */
	public function edit() {
		
		$message = "";
		$status = "";
		
		$id = 0;
		if (isset($_GET['id']))		$id = $_GET['id'];
		if (isset($_POST['id']))	$id = $_POST['id'];
		
		// on update
		if (isset($_POST['update'])) {
			if ($this->model->update($_POST)) {
				$message = "Record successfully updated";
				$status = "success";
			} else {
				$message = "No rows were affected";
				$status = "error";
			}
			
		// on delete
		} else if(isset($_POST['delete'])) {
			if ($this->model->delete($id)) {
				$message = "Record successfully deleted";
				$status = "success";
			} else {
				$message = "Error deleting record";
				$status = "error";
			}
		
		// on insert via add new page
		} else if(isset($_POST['add'])) {
			$id = $this->model->insert($_POST);
			if (0 < $id) {
				$message = "Record successfully inserted";
				$status = "success";
			} else {
				$message = "Error adding record";
				$status = "error";
			}
		}
		
		$table_name = $this->model->get_table_name();
		$primary_key = $this->model->get_primary_key();
		$columns = $this->model->get_columns();		
		$row = $this->model->get_row($id);

		include(FILE_VIEW_EDIT);
	}
	
	/**
     * Configuration page
     *
     */
	public function settings() {
		
		// read settings file
		$settings = parse_ini_file(FILE_INI);
		$status = "";
		$message = "";
		
		// update ini file
		if (isset($_POST['apply'])) {
			
			// check table validity
			$message = $this->model->validate($_POST['table_name']);
			if ($message != "") {
				$status = "error";

			} else {
				
				// gather new setting params
				$settings['table_name'] = $_POST['table_name'];
				$settings['rows_per_page'] = $_POST['rows_per_page'];
				$settings['csv_file_name'] = $_POST['csv_file_name'];
				$settings['csv_encoding'] = $_POST['csv_encoding'];
				
				// switch table
				$this->model = new Model($settings['table_name']);
				
				// update ini file
				$fp = fopen(FILE_INI, 'w');
				foreach ($settings as $k => $v){
					if (false == fputs($fp, "$k = $v" . NEW_LINE)) {
						$status = "error";
					}
				}
				fclose($fp);
				
				$status = "success";
				$message = "Settings successfully changed";
			}

		// restore ini file with default settings
		} else if (isset($_POST['restore'])) {
			
			if (copy(FILE_INI_DEFAULT, FILE_INI)) {	
				$settings = parse_ini_file(FILE_INI);
				$this->model = new Model($settings['table_name']);
				
				$status = "success";
				$message = "Defult settings successfully restored";

			} else {
				$status = "error";
				$message = "Error: default config file not found";
			}
		}

		$this->rows_per_page = $settings['rows_per_page'];
		
		// csv settings
		$this->csv['file_name'] = $settings['csv_file_name'];
		$this->csv['encoding']  = $settings['csv_encoding'];
		
		$table_name = $this->model->get_table_name();
		$table_options = $this->model->get_table_options();
		
		include(FILE_VIEW_SETTINGS);
	}
	
}
?>