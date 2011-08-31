<?php

#this functions are for working with items. items saved as files.
##far far away in the future there may be mysql.php for working with mysql base.
##switching will be done in config

function get_item($s_dir,$item,$source) { #external
#
#get filename realfn. use it
$file = get_mapping($s_dir,$item,$source);
if ($file === false) return false;
#read contents	
$fitem = fopen($s_dir . "/" . $file, "r");
$data = fread($fitem, filesize($s_dir . "/" . $file));
fclose($fitem);
return $data;
};

function new_item($s_dir,$item,$data,$source) { #external
#creates item, mapping, hash, ...
$temp = explode("/",$item);
lg("Exploded");
array_pop($temp);
lg("POPPED");
$temp2 = implode("/",$temp);
$temp2 = $s_dir . "/" . $temp2;
lg("IMPLODED $temp2");
if (! is_dir($temp2)) mkdir($temp2);
#is item here really not exists? Is it for shure? There was some checks in add_response().. Yep, there is a check.
make_mapping($s_dir,$item,$source);
#make map: map_$realfn=$item
return write_item($s_dir,$item,$data,$source);
};

function write_item($s_dir,$item,$data,$source) { #external
#writes contents	
#gen new filename realfn. use it
$file = get_mapping($s_dir,$item,$source);
if ($file === false) return false;
#write contents
$fitem = fopen($s_dir . "/" . $file, "w");
if ($fitem === false) return false;
fwrite($fitem, base64_decode($data));
fclose($fitem);
$f_state = $s_dir . "_" . $source . ".state";
#lg("fstate: $f_state");
$STATE = Array();
file_load($f_state, $STATE); #it can be faster to do append
$STATE["hash_$item"] = md5($data);
#lg("item: $item, md5: " . $STATE["hash_$item"]);
file_save($f_state, $STATE);
#DONE:don't forget to do unset($STATE[$item]) in Map function;
return true;
};

function remove_item($s_dir,$item,$source) { #external
if (! is_dir($s_dir)) mkdir($s_dir);
#lg("removing " . $s_dir . "/" . $item . ". is file" . (is_file($s_dir . "/" . $item) ? "true" : "false"));
if (! unlink($s_dir . "/" . $item)) lg("unable to remove $s_dir/$item");
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
$file = get_mapping($s_dir,$item,$source);
unset($STATE["map_$file"]);
unset($STATE["hash_$item"]);
file_save($f_state, $STATE);
unset($STATE);
return true;
}

function get_mapping($s_dir,$item,$source) { #internal. should be..
#unset($STATE); #what a hell? who made $STATE global?
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
$result = false;
#map_$realfn=$item
foreach ($STATE as $key => $value) {
	if (strtolower($value) == strtolower($item)) {
		#$result = substr($key, 4); #remove "map_" # this would convert the file name to lower :(
		lg("ITEM = $item KEY = $key VALUE = $value");		
		$temp = explode("/",$item);
		$result = $temp[1];
		break;
	};
};
unset($STATE);
return $result; #realfn
}

function make_mapping($s_dir,$item,$source) { #internal
#creates first mapping. because it's first, it maps $item to $item
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$STATE["map_$item"] = $item;
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
}

function remove_mapping($s_dir,$item,$source) {#external, rare?
$f_state = $s_dir . "_" . $source . ".state";
$file = get_mapping($s_dir,$item,$source);
file_load($f_state, $STATE); #it can be faster to do append
unset($STATE["map_$file"]);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function remove_hash($s_dir,$item,$source) {#external, rare?
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
unset($STATE["hash_$item"]);
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function get_hash($s_dir,$item,$source) {#external, rare?
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$result = false;
if (array_key_exists("hash_$item", $STATE)) $result = $STATE["hash_$item"];
unset($STATE);
return $result;
};

function write_hash($s_dir,$item,$source,$hash) {#external, weird
#used when there is an item, created from another device, to copy it's hash
#..or if item is changed (going to be sent using Replace command)
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE); #it can be faster to do append
$STATE["hash_" . $item] = $hash;
file_save($f_state, $STATE); # or a shared $STATE
unset($STATE);
return true;
};

function exists_item($s_dir,$item,$source) { #external
$realfn = get_mapping($s_dir,$item,$source);
if ($realfn === false) return false;
return file_exists($s_dir . "/" . $realfn);
#return bool;
};

function rename_item($s_dir,$itemf,$itemt,$source) { #external
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
#map_$realfn=$item
$realfn = get_mapping($s_dir,$itemf,$source);
$STATE["map_$realfn"] = $itemt;
$hash = $STATE["hash_$itemf"];
unset($STATE["hash_$itemf"]);
$STATE["hash_$itemt"] = $hash;
file_save($f_state, $STATE);
unset($STATE);
return;
#return bool;
};

function list_items($s_dir,&$ITEMS,$source){ #external
#$ITEMS should be $ITEMS[$item] = $hash
#items = _files_! list FILES!
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
#map_$realfn=$item
$fcnt = 0;
$umpd = 0;
$SDir = opendir($s_dir);
while (false !== ($file = readdir($SDir))) {
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
file_save($f_state, $STATE);
unset($STATE);
lg("found $fcnt files, $umpd unmapped (new)");
return true;
};

function list_hashes($s_dir,&$HASHES,$source){ #external
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
file_save($f_state, $STATE);
unset($STATE);
lg("found $fcnt hashes");
return true;
};

?>
