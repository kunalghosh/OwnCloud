<?php
#this file contains everything about conflict resolving

function conflict_ser($s_dir,$item,$data,$source,&$result) {
	$result = "419";
	return true;
}

function conflict_cli($s_dir,$item,$data,$source,&$result) {
	$result = "208";
	write_item($s_dir,$item,$data,$source);
	return true;
}

function conflict_dup($s_dir,$item,$data,$source,&$result) {
	$result = "209";
	$newname = $item . date('YmdHis') . mt_rand();
	lg("duping $item to $newname");
	if (! rename("$s_dir/$item", "$s_dir/$newname")) {
		lg("somewhy renaming failed");
		return false;
	};
	lg("Renamed $s_dir/$item to $s_dir/$newname");
	return true;
}

function conflict_del($s_dir,$item,$data,$source,&$result) {
	$result = "409";
	lg("DELETING $s_dir/$item !");
	if (! unlink("$s_dir/$item")) {
		lg("somewhy deleting failed");
		return false;
	};
	return true;
}

function conflict_mer($s_dir,$item,$data,$source,&$result) {
	lg("merging not implemented");
	return false;
}


function conflict_solve($try,$s_dir,$item,$data,$source) {
global $conflict_action1;
global $conflict_action2;
$result = "409";
#there should be a conflict-solving mechanism <<<< !!!!
if ($try == 1) {
	$ca = $conflict_action1;
} else {
	$ca = $conflict_action2;
};
$cr = false; #conflict resolved
switch ($ca) { #ser, cli, dup, del, mer
case "ser":
	$cr = conflict_ser($s_dir,$item,$data,$source,$result);
	break;
case "cli":
	$cr = conflict_cli($s_dir,$item,$data,$source,$result);
	break;
case "dup":
	$cr = conflict_dup($s_dir,$item,$data,$source,$result);
	break;
case "del":
	$cr = conflict_del($s_dir,$item,$data,$source,$result);
	break;
case "mer":
	if ($try == 1)
	{
		$cr = conflict_mer($s_dir,$item,$data,$source,$result);
	} else {
		lg("merging not allowed as 2nd action.");
		$cr = false;
	};
	break;
default:
	lg("$ca is not ser, cli, dup, del or mer");
};
if (! $cr) {
	if ($try == 1) {
		$result = conflict_solve($try+1,$s_dir,$item,$data,$source);
	} else {
		lg("conflict not resolved. client wins...");
	};
};
return $result;
}
?>
