<link type='text/css' href='<?php echo FILE_CSS ?>' rel='stylesheet' />
<div class='wrap'>
<h2>Simple Table Manager - Edit</h2>
<h3><?php echo $table_name ?></h3>

<?php 
	if ($status == "error") {
		echo "<div class='error'><p>$message</p></div>";
	} else if ($status == "success") {
		echo "<div class='updated'><p>$message</p></div>";
	}
?>

	<div class='subsubsub'>
	<a href="<?php echo $this->url['list'] ?>">&lt;&lt; Return to list</a>
	</div>
		
<?php if (!empty($row)) { ?>
		<form method='post' action='<?php echo $this->url['edit'] ?>'>
		<table class='wp-list-table widefat fixed'>
<?php
		require_once("util.php");
		foreach ($row as $name => $value) {
			if ($name == $primary_key) {
				echo "<tr><th class='simple-table-manager'>$name</th><td><input type='number' readonly='readonly' name='$name' value='$value'/></td></tr>";
			} else {
				echo "<tr><th class='simple-table-manager'>$name</th><td>" . data_type2html_input($columns[$name], $name, $value) . "</td></tr>";
			}
		}
?>
		</table>
		<div class="tablenav bottom">
		<input type='submit' name='update' value='Update' class='button'>&nbsp;
		<input type='submit' name='delete' value='Delete' class='button' onclick="return confirm('Are you sure you want to delete this record?')">
		</div>
		<input type="hidden" name="id" value="<?php echo $id ?>">
		</form>
<?php } ?>
</div>