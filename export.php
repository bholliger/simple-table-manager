<?php
	define('DELMITER', ',');
	define('NEW_LINE', "\r\n");
	
	// Deny access NOT coming from export page
	session_start();
	if (!isset($_SESSION['export']) ) 	exit(0);
	if (!isset($_POST['task_id']) ) 	exit(0);
	if (md5($_SESSION['export']) != $_POST['task_id'])  exit(0);

	// read setting file
	$settings = parse_ini_file("./settings.ini");

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=" . $settings['csv_file_name']);

	require_once('../../../wp-load.php');
	global $wpdb;
	
	// field names
	foreach ($wpdb->get_col("DESC " . $settings['table_name'], 0) as $field_name) {
		print($field_name . DELMITER);
	}
	print(NEW_LINE);
	
	// data
	foreach ($wpdb->get_results("SELECT * FROM " . $settings['table_name']) as $row ){
		foreach ($row as $k => $v ) {
			$str = preg_replace('/"/', '""', $v);
			print("\"" . mb_convert_encoding($str, $settings['csv_encoding'], 'UTF-8')."\"" . DELMITER);
      	}
		print(NEW_LINE);
	}
