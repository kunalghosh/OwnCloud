<?php

#this functions are for working with items. items saved as files.
##far far away in the future there may be mysql.php for working with mysql base.
##switching will be done in config

function get_item($s_dir,$item,$source) { #external
    global $appName;
#
#get filename realfn. use it
OC_Log::write( $appName,"FUNCTION: GET_ITEM in files_fileSync.php item = $item",  OC_Log::DEBUG);
$file = get_mapping($s_dir,$item,$source);
OC_Log::write( $appName,"FUNCTION: GET_ITEM IN files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
if ($file === false) return false;
#read contents	
$fitem = OC_Filesystem::fopen($file, "r");
$data = fread($fitem, OC_Filesystem::filesize($file));
fclose($fitem);
return $data;
};

function new_item($s_dir,$item,$data,$source) { #external
    global $appName;
#creates item, mapping, hash, ...
$temp = explode("/",$item);
OC_Log::write( $appName,"Exploded",  OC_Log::DEBUG);
array_pop($temp);
OC_Log::write( $appName,"POPPED",  OC_Log::DEBUG);
$temp2 = implode("/",$temp);
$temp2 = $s_dir . "/" . $temp2;
OC_Log::write( $appName,"IMPLODED $temp2",  OC_Log::DEBUG);
if (!OC_Filesystem::is_dir($temp2))    OC_Filesystem::mkdir($temp2);
#is item here really not exists? Is it for shure? There was some checks in add_response().. Yep, there is a check.
OC_Log::write( $appName,"NEW_ITEM temp2 = $temp2 item = $item s_dir = $s_dir",  OC_Log::DEBUG);
make_mapping($s_dir,$item,$source);
#make map: map_$realfn=$item
return write_item($s_dir,$item,$data,$source);
};

function write_item($s_dir,$item,$data,$source) { #external
    global $appName;
#writes contents	
#gen new filename realfn. use it
OC_Log::write( $appName,"FUNCTION: WRITE_ITEM in files_fileSync.php item = $item",  OC_Log::DEBUG);
$file = get_mapping($s_dir,$item,$source);
OC_Log::write( $appName,"FUNCTION: WRITE_ITEM IN files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
if ($file === false) return false;
#write contents
$fitem = OC_Filesystem::fopen($file, "w");
if ($fitem === false) return false;
fwrite($fitem, base64_decode($data));
fclose($fitem);
$f_state = $s_dir . "_" . $source . ".state";
#lg("fstate: $f_state");
$STATE = Array();
file_load($f_state, $STATE); #it can be faster to do append
$STATE["hash_$item"] = md5($data);
#lg("item: $item, md5: " . $STATE["hash_$item"]);
OC_Log::write( $appName,"Function : write_item f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE);
#DONE:don't forget to do unset($STATE[$item]) in Map function;
return true;
};

function remove_item($s_dir,$item,$source) { #external
    global $appName;
if (!OC_Filesystem::is_dir($s_dir))    OC_Filesystem::mkdir($s_dir);
#lg("removing " . $s_dir . "/" . $item . ". is file" . (is_file($s_dir . "/" . $item) ? "true" : "false"));
if (!OC_Filesystem::unlink($item)) OC_Log::write( $appName,"unable to remove $s_dir/$item",  OC_Log::DEBUG);
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
OC_Log::write( $appName,"FUNCTION: REMOVE_ITEM in files_fileSync.php item = $item",  OC_Log::DEBUG);
$file = get_mapping($s_dir,$item,$source);
OC_Log::write( $appName,"FUNCTION:  REMOVE_ITEM in files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
unset($STATE["map_$file"]);
unset($STATE["hash_$item"]);
OC_Log::write( $appName,"Function : remove_item f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE);
unset($STATE);
return true;
}

function get_mapping($s_dir,$item,$source) { #internal. should be..
    global $appName;
#unset($STATE); #what a hell? who made $STATE global?
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
OC_Log::write( $appName,"GET_MAPPING f_state = $f_state",  OC_Log::DEBUG);
file_load($f_state, $STATE);
$result = false;
#map_$realfn=$item
foreach ($STATE as $key => $value) {
	if (strtolower($value) == strtolower($item)) {
		$result = substr($key, 4); #remove "map_" # this would convert the file name to lower :(
		OC_Log::write( $appName,"ITEM = $item KEY = $key VALUE = $value",  OC_Log::DEBUG);		
		#$temp = explode("/",$item);
		#$result = $temp[1];
		#$result = $value;
		break;
	};
};
unset($STATE);
return $result; #realfn
}

function make_mapping($s_dir,$item,$source) { #internal
    global $appName;
#creates first mapping. because it's first, it maps $item to $item
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$STATE["map_$item"] = $item;
OC_Log::write( $appName,"Function : make_mapping f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
}

function remove_mapping($s_dir,$item,$source) {#external, rare?
    global $appName;
$f_state = $s_dir . "_" . $source . ".state";
OC_Log::write( $appName,"FUNCTION: REMOVE_MAPPING in files_fileSync.php item = $item",  OC_Log::DEBUG);
$file = get_mapping($s_dir,$item,$source);
OC_Log::write( $appName,"FUNCTION:  REMOVE_MAPPING in files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
file_load($f_state, $STATE); #it can be faster to do append
unset($STATE["map_$file"]);
OC_Log::write( $appName,"Function : remove_mapping f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function remove_hash($s_dir,$item,$source) {#external, rare?
    global $appName;
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
unset($STATE["hash_$item"]);
OC_Log::write( $appName,"Function : remove_hash f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function get_hash($s_dir,$item,$source) {#external, rare?
    global $appName;
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$result = false;
if (array_key_exists("hash_$item", $STATE)) $result = $STATE["hash_$item"];
unset($STATE);
return $result;
};

function write_hash($s_dir,$item,$source,$hash) {#external, weird
    global $appName;
#used when there is an item, created from another device, to copy it's hash
#..or if item is changed (going to be sent using Replace command)
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$STATE["hash_" . $item] = $hash;
OC_Log::write( $appName,"Function : write_hash f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function exists_item($s_dir,$item,$source) { #external
    global $appName;
OC_Log::write( $appName,"FUNCTION: EXISTS_ITEM in files_fileSync.php item = $item",  OC_Log::DEBUG);
$realfn = get_mapping($s_dir,$item,$source);
OC_Log::write( $appName,"FUNCTION:  EXISTS_ITEM in files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
if ($realfn === false){
	OC_Log::write( $appName,"EXISTS_ITEM in files_fileSync.php returned false for s_dir = $s_dir item = $item source = $source",  OC_Log::DEBUG);
	return false;
}
if ( !OC_Filesystem::is_dir($s_dir)){
#check if the directory exists, if not return that
#the item doesn't exist.
	OC_Log::write( $appName,"EXISTS_ITEM in files_fileSync.php returned false for s_dir = $s_dir item = $item source = $source",  OC_Log::DEBUG);
	return false;	
}
return file_exists($s_dir . "/" . $realfn);
#return bool;
};

function rename_item($s_dir,$itemf,$itemt,$source) { #external
    global $appName;
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
#map_$realfn=$item
OC_Log::write( $appName,"FUNCTION: RENAME_ITEM in files_fileSync.php item = $item",  OC_Log::DEBUG);
$realfn = get_mapping($s_dir,$itemf,$source);
OC_Log::write( $appName,"FUNCTION:  RENAME_ITEM in files_fileSync.php AFTER GET_MAPPING FILE=$file",  OC_Log::DEBUG);
$STATE["map_$realfn"] = $itemt;
$hash = $STATE["hash_$itemf"];
unset($STATE["hash_$itemf"]);
$STATE["hash_$itemt"] = $hash;
OC_Log::write( $appName,"Function : rename_item f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE);
unset($STATE);
return;
#return bool;
};

function list_items($s_dir,&$ITEMS,$source){ #external
    global $appName;
#$ITEMS should be $ITEMS[$item] = $hash
#items = _files_! list FILES!
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
#map_$realfn=$item
$fcnt = 0;
$umpd = 0;
$SDir = OC_Filesystem::opendir($s_dir);
while (false !== ($file = OC_Filesystem::opendir($SDir))) {//was PHP default readdir() may cause problems later
	if ($file != "." && $file != "..") {
		if ((! array_key_exists("map_$file", $STATE)) || (empty($STATE["map_$file"]))) {
			$STATE["map_$file"] = $file;
			$ITEMS[$file] = md5_file("$s_dir/$file"); #add item to array
			$umpd++;
		} else {
			$subst = $STATE["map_$file"];
			$ITEMS[$subst] = md5_file("$s_dir/$file");
		};
		$fcnt++;
	};
};
OC_Log::write( $appName,"Function : list_items f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE);
unset($STATE);
OC_Log::write( $appName,"found $fcnt files, $umpd unmapped (new)",  OC_Log::DEBUG);
return true;
};

function list_hashes($s_dir,&$HASHES,$source){ #external
    global $appName;
#$HASHES should be $HASHES[$item] = $hash
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
$fcnt = 0;
foreach ($STATE as $item => $hash) {
	if (strpos($item, "hash_") !== false) {
		$HASHES[substr($item, 5)] = $hash;
		$fcnt++;
	};
};
OC_Log::write( $appName,"Function : list_hashes f_state = $fstate ",  OC_Log::DEBUG);
file_save($f_state, $STATE);
unset($STATE);
OC_Log::write( $appName,"found $fcnt hashes",  OC_Log::DEBUG);
return true;
};

?>
