<?php
/*
Plugin Name: CRUD a Table
Description: A plugin that enables editing table records and exporting them to CSV files through minimal database interface from your wp-admin page menu.
Version: 1.1
Author: Ryo Inoue
Author URI: http://www16060ui.sakura.ne.jp
*/

require_once('core.php');
$manager = new SimpleTableManager();

