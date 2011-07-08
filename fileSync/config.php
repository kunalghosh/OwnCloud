<?php
function parse_config(&$CONFIG) {
##parse_config() -> $CONFIG
	global $base_dir, $do_log, $clear_log, $keep_exchange;
	global $dbuser, $dbpass, $dbtype;
	global $conflict_action1, $conflict_action2;
	global $unrestricted;
#parse_ini_file?	
	
// Let's parse the config file and get our settings
$config = simplexml_load_file('config/config.xml');
#$compression = $config->config->compression; #unused. should be no, yes, auto
# but anything, except auto can refuse to work
$base_dir = $config->config->base_dir;
if (empty($base_dir)) {
	$basedir = getcwd();
	lg("base_dir is empty. using $base_dir");
};
$do_log = $config->config->do_log;
if (empty($do_log)) {
	$do_log = 1;
	lg("do_log is not set. check config/config.xml for syntax errors");
};
if ($config === FALSE) lg("CONFIG DAMAGED! HOPE YOU KNOW WHAT YOU ARE DOING!");
$clear_log = $config->config->clear_log;
$keep_exchange = $config->config->keep_exchange;
$dbuser = $config->config->database->dbuser; #unused
$dbpass = $config->config->database->dbpass; #unused
$dbtype = $config->config->database->dbtype; #unused
$conflict_action1 = strtolower(substr($config->config->conflicts->action1, 0, 3));
$conflict_action2 = strtolower(substr($config->config->conflicts->action2, 0, 3));
$unrestricted = $config->config->unrestricted;
#lg("config parsed");
##/parse_config()
}

?>
