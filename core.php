<?php
define('FILE_INI', dirname(__FILE__) . '/settings.ini');
define('FILE_INI_DEFAULT', dirname(__FILE__) . '/settings.ini.default');
define('FILE_CSS',  plugin_dir_url(__FILE__) . "style-admin.css");
define('FILE_EXPORT',  plugin_dir_url(__FILE__) . "export.php");

/**
 * Plugin Class
 *
 */
class SimpleTableManager {
	
	private $table_name;
	private $fields;
	private $primary;
	private $rows_per_page;
	private $slug;
	
	/**
     * Constructor - sets table variables and slugs
     *
     */
	public function __construct() {
		
		// read settings
		$settings = parse_ini_file(FILE_INI);
		
		// table
		$this->set_table($settings['table_name'], $settings['rows_per_page']);
		
		// slugs & menu
		$this->slug['list']     = $settings['base_slug'] . '_list';
		$this->slug['add']      = $settings['base_slug'] . '_add';
		$this->slug['edit']     = $settings['base_slug'] . '_edit';
		$this->slug['settings'] = $settings['base_slug'] . '_settings';
		add_action('init', array($this, 'init_session'));
		add_action('admin_menu', array($this, 'add_menu'));
	}
	
	/**
     * Enables session use
     *
     */
	public function init_session() {
		if (!session_id()) {
	        session_start();
	    }
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

		echo "<link type='text/css' href='" . FILE_CSS . "' rel='stylesheet' />";
		echo "<div class='wrap'>";
		echo "<h2>Simple Table Manager - List</h2>";
		echo "<h3>" . $this->table_name . "</h3>";

		global $wpdb;
		
		// key word search
		$key_word = "";
		$where_qry = "";
		if (isset($_POST['search']))	$key_word = $_POST['search'];
		if (isset($_GET['search']))		$key_word = $_GET['search'];
		if ($key_word != "") {
			$tmp = array();
			foreach ($this->fields as $f) {
				$tmp[] = $wpdb->prepare(" $f LIKE '%%%s%%'", $key_word);
			}
			$where_qry = " WHERE " . implode(" OR ", $tmp);
			echo "<div class='updated'><p>Search results for: " . $key_word . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='" . admin_url('admin.php?page=' . $this->slug['list']) . "'>Exit Search</a></p></div>";
		}
		
		// order by
		$order_by = "";
		$order = "";
		$order_qry = "";
		if (isset($_GET['orderby'])) {
			if ($_GET['orderby'] != "") {
				$order_by = esc_sql($_GET['orderby']);
				$order = esc_sql($_GET['order']);
				$order_qry = " ORDER BY $order_by $order ";
			}
		}
		
		echo "<form action='" . admin_url('admin.php?page=' . $this->slug['list']) . "' method='post' name='search'>";
		echo "<p class='search-box'>";
		echo "<input type='search' name='search' placeholder='Search &hellip;' value='$key_word' />";
		echo "</form>";
		
		// manage record quantity
		$begin_row = 0;
		if (isset($_GET['beginrow'])){	
			if (is_numeric($_GET['beginrow'])){
				$begin_row = $_GET['beginrow'];
			}
		}
		$total = $wpdb->get_var("SELECT COUNT(*) FROM " . $this->table_name . $where_qry);	// count all data rows
		$next_begin_row = $begin_row + $this->rows_per_page;
		if ($total < $next_begin_row) $next_begin_row = $total;
		$last_begin_row = $this->rows_per_page * (floor($total / $this->rows_per_page) - 1);

		if ($total <= 0) {
			echo "<div class='subsubsub'>No results found.</div>";
		} else {
			
			echo "<div class='subsubsub'>";
			echo "<a href=" . admin_url('admin.php?page=' . $this->slug['add']) . ">Add New</a>&nbsp;&nbsp;";
			echo "<a href='' onclick='document.export.submit(); return false;'>Export CSV</a> ";
			echo "</div>";
			
			// export csv via post
			$task_id = mt_rand();
			$_SESSION['export'] = $task_id;
			echo "<form action='" . FILE_EXPORT . "' target='_blank' method='post' name='export'>";
			echo "<input type='hidden' value='" . md5($task_id) . "' name='task_id'>";
			echo "</form>";
			
			// data list
			echo "<table class='wp-list-table widefat fixed'>";

			// field names
			echo "<thead>";
			echo "<th></th>";
			foreach ($this->fields as $f) {
				if ($f == $order_by) {
					if ("asc" == $order) {
						echo "<th scope='col' class='manage-column sortable asc'  style=''>";
						echo "<a href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038;search=$key_word&#038;orderby=$f&#038;order=desc'>";
					} else {
						echo "<th scope='col' class='manage-column sortable desc'  style=''>";
						echo "<a href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038;search=$key_word&#038;orderby=$f&#038;order=asc'>";
					}
				} else {
					echo "<th scope='col' class='manage-column sortable desc'  style=''>";
					echo "<a href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038;search=$key_word&#038;orderby=$f&#038;order=asc'>";
				}
				echo "<span>$f</span><span class='sorting-indicator'></span></a></th>";
			}
			echo "</thead>";
			
			// decorate rows
			$row_bgcolor = array('simple-table-manager-list-all-odd', 'simple-table-manager-list-all-even');
			$row_bgcolor_index = 0;

			// data contents
			echo "<tbody>";
			$result = $wpdb->get_results("SELECT * FROM " . $this->table_name . $where_qry . $order_qry . " LIMIT $begin_row, $this->rows_per_page");
			foreach ($result as $row ){
				echo "<tr>";
				foreach ($row as $k => $v ) {
					if ($k == $this->primary) {
						echo "<td class='" . $row_bgcolor[$row_bgcolor_index] . "' nowrap><a href='" . admin_url('admin.php?page=' . $this->slug['edit'] . '&#038id=' . $v) . "'>Edit</a></td>";
					}
					echo "<td class='" . $row_bgcolor[$row_bgcolor_index] . "'>$v</td>";
				}
				echo "</tr>";
				$row_bgcolor_index = ($row_bgcolor_index + 1) % count($row_bgcolor);
			}
			echo "</tbody>";
			echo "</table>";
		
			// navigation
			echo "<div class='tablenav bottom'>";
			echo "<div class='tablenav-pages'>";
			echo "<span class='displaying-num'>Total ". number_format($total) . " </span>";
			echo "<span class='pagination-links'>";

			if (0 < $begin_row) {
				echo "<a title='first page' href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038beginrow=0&#038search=$key_word&#038;orderby=$order_by&#038;order=$order'>&laquo;</a>";
				echo "<a title='previous page' href=" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038beginrow=". ($begin_row - $this->rows_per_page) . "&search=$key_word&#038;orderby=$order_by&#038;order=$order'>&lsaquo;</a>";
			}else {
				echo "<a class='first-page disabled' title='first page'>&laquo;</a>";
				echo "<a class='prev-page disabled' title='previous page'>&lsaquo;</a>";
			}
			echo "<span class='paging-input'> " . number_format($begin_row + 1) . " - <span class='total-pages'>" . number_format($next_begin_row) . " </span></span>";
			if ($next_begin_row < $total) {
				echo "<a class='next-page' title='next page' href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038beginrow=$next_begin_row&#038search=$key_word&#038;orderby=$order_by&#038;order=$order'>&rsaquo;</a>";
				echo "<a class='last-page' title='last page' href='" . admin_url('admin.php?page=' . $this->slug['list']) . "&#038beginrow=$last_begin_row&#038search=$key_word&#038;orderby=$order_by&#038;order=$order'>&raquo;</a>";
			}else {
				echo "<a class='next-page disabled' title='next page'>&rsaquo;</a>";
				echo "<a class='last-page disabled' title='last page'>&raquo;</a>";
			}
			echo "</span>";
			echo "</div><br class='clear' />";
			echo "</div>";
		}
		
		echo "</div>";
	}
	
	/**
     * Adds new data to table
     *
     */
	public function add_new() {
		
		$this->print_header("Simple Table Manager - Add New", "");
		echo "<form method='post' action='" . admin_url('admin.php?page=' . $this->slug['edit']) . "'>";
		echo "<table class='wp-list-table widefat fixed'>";

		foreach ($this->fields as $f) {
			if ($f == $this->primary) {
				echo "<tr><th class='simple-table-manager'>$f</th><td>(Auto Increment)</td></tr>";
			} else {
				echo "<tr><th class='simple-table-manager'>$f</th><td><input type='text' name='$f' value=''/></td></tr>";
			}
		}
?>
		</table>
		<div class="tablenav bottom">
		<input type='submit' name='insert' value='Add' class='button'>
		</div>
		</form>
	</div>
<?php
	}
	
	/**
     * Edit data
     *
     */
	public function edit() {
		$current_vals = array();
		$current_vals[$this->primary] = $_GET["id"];
		$message = "";
		global $wpdb;
		
		// on update
		if (isset($_POST['update'])) {
			foreach ($this->fields as $f) {
				$current_vals[$f] = esc_sql($_POST[$f]);
			}
			if ($wpdb->update($this->table_name, $current_vals, array($this->primary => $current_vals[$this->primary]))) {
				$message = "<div class='updated'><p>Record successfully updated</p></div>";	
			} else {
				$message = "<div class='error'><p>No rows were affected</p></div>";
			}
		
		// on delete
		} else if(isset($_POST['delete'])) {
			if ($wpdb->query($wpdb->prepare("DELETE FROM " . $this->table_name . " WHERE " . $this->primary . " = %s", $current_vals[$this->primary]))) {
				$message = "<div class='updated'><p>Record successfully deleted</p></div>";
			} else {
				$message = "<div class='error'><p>Error deleting record</p></div>";
			}
		
		// on insert via 'Add New' page
		} else if(isset($_POST['insert'])){
			foreach ($this->fields as $f) {
				$current_vals[$f] = esc_sql($_POST[$f]);
			}
			$current_vals[$this->primary] = $wpdb->get_var("SELECT MAX(" . $this->primary . ")+1 FROM " . $this->table_name);
			if ($wpdb->insert($this->table_name, $current_vals)) {
				$message = "<div class='updated'><p>New record successfully added</p></div>";
			} else {
				$message = "<div class='error'><p>Error adding record</p></div>";
			}
			
		// default
		} else {
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table_name . " WHERE " . $this->primary . " = %s", $current_vals[$this->primary]));
			foreach ($row as $k => $v ) {
				$current_vals[$k] = $v;
			}
		}
		
		$this->print_header("Simple Table Manager - Edit", $message);

		echo "<form method='post' action='" . admin_url('admin.php?page=' . $this->slug['edit']) . "&id=" . $current_vals[$this->primary] ."'>";
		echo "<table class='wp-list-table widefat fixed'>";
	
		if(!isset($_POST['delete'])) {
			foreach ($this->fields as $f) {
				if ($f == $this->primary) {
					echo "<tr><th class='simple-table-manager'>$f</th><td><input type='text' readonly='readonly' name='$f' value='$current_vals[$f]'/></td></tr>";
				} else {
					echo "<tr><th class='simple-table-manager'>$f</th><td><input type='text' name='$f' value='$current_vals[$f]'/></td></tr>";
				}
			}
?>
			</table>
			<div class="tablenav bottom">
			<input type='submit' name='update' value='Update' class='button'>&nbsp;
			<input type='submit' name='delete' value='Delete' class='button' onclick="return confirm('Are you sure you want to delete this record?')">
			</div>
<?php
			echo "</form>";
		}
		
		echo "</div>";
	}
	
	/**
     * Displays plugin configuration page
     *
     */
	public function settings() {
		
		// read settings file
		$settings = parse_ini_file(FILE_INI);
		$message = "";
		global $wpdb;
		
		// update ini file
		if (isset($_POST['apply'])) {
			
			// check table validity
			$wpdb->get_results("SHOW KEYS FROM " . $_POST['table_name'] . " WHERE Key_name = 'PRIMARY'");
			$num_of_pks = $wpdb->num_rows;
			$results = $wpdb->get_results("SHOW FIELDS FROM " . $_POST['table_name']);

			if (1 < $num_of_pks) {
				$message = "<div class='error'><p>Error: table " . $_POST['table_name'] . " has multiple primary keys</p></div>";
			
			} else if ($results[0]->Key != 'PRI') {
				$message = "<div class='error'><p>Error: table " . $_POST['table_name'] . "'s primary key is not set at first column</p></div>";
			
			} else if (!stristr($results[0]->Type, 'int')) {
				$message = "<div class='error'><p>Error: table " . $_POST['table_name'] . "'s primary key is not an int data type</p></div>";
			
			// change settings
			} else {
				$settings['table_name'] = $_POST['table_name'];
				$settings['rows_per_page'] = $_POST['rows_per_page'];
				$settings['csv_file_name'] = $_POST['csv_file_name'];
				$settings['csv_encoding'] = $_POST['csv_encoding'];

				$fp = fopen(FILE_INI, 'w');
				foreach ($settings as $k => $v){
					if (false == fputs($fp, "$k = $v\n")) {
						echo "error";
					}
				}
				fclose($fp);
				
				$message = "<div class='updated'><p>Settings successfully changed</p></div>";
				$this->set_table($settings['table_name'], $settings['rows_per_page']);
			}

		// restore ini file with default settings
		} else if (isset($_POST['restore'])) {
			
			if (copy(FILE_INI_DEFAULT, FILE_INI)) {	
				$settings = parse_ini_file(FILE_INI);
				$this->set_table($settings['table_name'], $settings['rows_per_page']);
				$message = "<div class='updated'><p>Defult settings successfully restored</p></div>";

			} else {
				$message = "<div class='error'><p>Error: default config file not found</p></div>";
			}
		}
		
		$this->print_header("Simple Table Manager - Settings", $message);

		echo "<form method='post' name='settings' action='" . $_SERVER['REQUEST_URI'] . "'>";
		echo "<table class='wp-list-table widefat fixed'>";
		
		echo "<tr><th class='simple-table-manager'>Table name</th><td><select name='table_name'>";
		foreach ($wpdb->get_results("SHOW TABLES") as $row ){
			foreach ($row as $k => $v){
				if ($settings['table_name'] == $v){
					echo "<option value='$v' selected>$v</option>";
				} else {
					echo "<option value='$v'>$v</option>";
				}
			}
		}
		echo "</select>";
		echo "<br /><i>Only one primary key must be set at first column with INT data type</i></td></tr>";

		echo "<tr><th class='simple-table-manager'>Max rows on page</th><td><input type='number' name='rows_per_page' value='" . $settings['rows_per_page'] . "'/></td></tr>";
		echo "<tr><th class='simple-table-manager'>CSV file name</th><td><input type='text' name='csv_file_name' value='" . $settings['csv_file_name'] . "'/></td></tr>";
		echo "<tr><th class='simple-table-manager'>CSV encoding</th><td><input type='text' name='csv_encoding' value='" . $settings['csv_encoding'] . "'/>";
?>
		</table>
		<div class="tablenav bottom">
		<input type='submit' name='apply' value='Apply Changes' class='button button-primary' />&nbsp;
		<input type='submit' name='restore' value='Restore Defaults' class='button' />
		</div>
		</form>
		</div>
<?php
	}
	
	/**
     * Sets table name, field names and pk
     *
     */
	private function set_table($table_name, $rows_per_page) {
		
		global $wpdb;
		$this->table_name = $table_name;
		$this->rows_per_page = $rows_per_page;
		
		$this->fields = array();
		foreach ($wpdb->get_col("DESC " . $this->table_name, 0) as $column_name) {
			$this->fields[] = $column_name;
		}
		
		$row = $wpdb->get_row("SHOW KEYS FROM " . $this->table_name . " WHERE Key_name = 'PRIMARY'");
		$this->primary = $row->Column_name;
	}
	
	/**
     * Prints the upper part of the page
     *
     */
	private function print_header($header_title, $message) {
		echo "<link type='text/css' href='" . FILE_CSS . "' rel='stylesheet' />";
		echo "<div class='wrap'>";
		echo "<h2>$header_title</h2>";
		echo "<h3>" . $this->table_name . "</h3>";
		echo $message;
		echo "<div class='subsubsub'>";
		echo "<a href=" . admin_url('admin.php?page=' . $this->slug['list']) . "><< Return to list</a>";
		echo "</div>";
	}
}
?>