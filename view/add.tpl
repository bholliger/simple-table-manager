<link type='text/css' href='<?php echo FILE_CSS ?>' rel='stylesheet' />
<div class='wrap'>
<h2>Simple Table Manager - Add New</h2>
<h3><?php echo $table_name ?></h3>

	<div class='subsubsub'>
	<a href="<?php echo $this->url['list'] ?>">&lt;&lt; Return to list</a>
	</div>
		
		<form method='post' action='<?php echo $this->url['edit'] ?>'>
		<table class='wp-list-table widefat fixed'>
<?php
		require_once("util.php");
		foreach ($columns as $name => $type) {
			if ($name == $primary_key) {
				echo "<tr><th class='simple-table-manager'>$name</th><td>(Auto Increment)</td></tr>";
			} else {
				echo "<tr><th class='simple-table-manager'>$name</th><td>" . data_type2html_input($columns[$name], $name, $value) . "</td></tr>";
			}
		}
?>
		</table>
		<div class="tablenav bottom">
		<input type='submit' name='add' value='Add' class='button'>
		</div>
		</form>
</div>