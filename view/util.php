<?php
	
function data_type2html_input($type, $name, $value) {

	switch ($type) {
		// numeric
		case "int":
		case "real":
			return "<input type='number' name='$name' value='$value'/>";
		
		// date
		case "date":
			return "<input type='date' name='$name' value='$value'/>";
		
		case "datetime":
		case "timestamp":
			return "<input type='text' name='$name' value='$value'/>";
//			return "<input type='datetime-local' name='$name' value='$value'/>";

		case "time":
			return "<input type='time' name='$name' value='$value'/>";
		
		// long text
		case "blob":
			return "<textarea name='$name'>$value</textarea>";
	}
	// default (text)
	return "<input type='text' name='$name' value='" . htmlspecialchars($value, ENT_QUOTES) . "'/>";
}

?>