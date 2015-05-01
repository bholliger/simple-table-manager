<?php
/*
Plugin Name: Simple Table Manager
Description: Enables editing table records and exporting them to CSV files through minimal database interface from your wp-admin page menu.
Version: 1.1
Author: Ryo Inoue
Author URI: https://profiles.wordpress.org/ryo-inoue/
*/

define('FILE_INI', dirname(__FILE__) . '/config/settings.ini');
define('FILE_INI_DEFAULT', dirname(__FILE__) . '/config/settings.ini.default');
define('FILE_CSS',  plugin_dir_url(__FILE__) . "/style/style-admin.css");
define('FILE_VIEW_LIST',  dirname(__FILE__) . "/view/list.tpl");
define('FILE_VIEW_SETTINGS',  dirname(__FILE__) . "/view/settings.tpl");
define('FILE_VIEW_EDIT',  dirname(__FILE__) . "/view/edit.tpl");
define('FILE_VIEW_ADD',  dirname(__FILE__) . "/view/add.tpl");
define('DELMITER', ',');
define('NEW_LINE', "\r\n");

require_once("controller.php");
require_once("model.php");

$control = new Controller();

?>