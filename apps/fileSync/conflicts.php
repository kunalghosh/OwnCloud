<?php
#this file contains everything about conflict resolving

function conflict_ser($s_dir,$item,$data,$source,&$result) {
        global $appName;
	$result = "419";
	return true;
}

function conflict_cli($s_dir,$item,$data,$source,&$result) {
        global $appName;
	$result = "208";
	write_item($s_dir,$item,$data,$source);
	return true;
}

function conflict_dup($s_dir,$item,$data,$source,&$result) {
        global $appName;
	$result = "209";
	$newname = $item . date('YmdHis') . mt_rand();
	/*
	lg("duping $item to $newname");
	if (! rename("$s_dir/$item", "$s_dir/$newname")) {
		lg("somewhy renaming failed");
		return false;
	};
	lg("Renamed $s_dir/$item to $s_dir/$newname");
	*/
	OC_Log::write( $appName,"CONFLICT RESOLUTION DUP:",  OC_Log::DEBUG);
	OC_Log::write( $appName,"COPY OLD FILE TO A NEW RENAMED FILE WITH TIMESTAMP:",  OC_Log::DEBUG);
	if (!OC_Filesystem::copy("$s_dir/$item", "$s_dir/$newname")) {
		OC_Log::write( $appName,"somewhy copying failed",  OC_Log::DEBUG);
		return false;
	};
	OC_Log::write( $appName,"WRITE NEW DATA INTO ORIGINAL FILE: $item",  OC_Log::DEBUG);
	write_item($s_dir,$item,$data,$source);
	return true;
}

function conflict_del($s_dir,$item,$data,$source,&$result) {
        global $appName;
	$result = "409";
	OC_Log::write( $appName,"DELETING $s_dir/$item !",  OC_Log::DEBUG);
	if (!OC_Filesystem::unlink("$item")) {
		OC_Log::write( $appName,"somewhy deleting failed",  OC_Log::DEBUG);
		return false;
	};
	return true;
}

function conflict_mer($s_dir,$item,$data,$source,&$result) {
        global $appName;
	OC_Log::write( $appName,"merging not implemented",  OC_Log::DEBUG);
	return false;
}


function conflict_solve($try,$s_dir,$item,$data,$source) {
    global $appName;
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
		OC_Log::write( $appName,"merging not allowed as 2nd action.",  OC_Log::DEBUG);
		$cr = false;
	};
	break;
default:
	OC_Log::write( $appName,"$ca is not ser, cli, dup, del or mer",  OC_Log::DEBUG);
};
if (! $cr) {
	if ($try == 1) {
		$result = conflict_solve($try+1,$s_dir,$item,$data,$source);
	} else {
		OC_Log::write( $appName,"conflict not resolved. client wins...",  OC_Log::DEBUG);
	};
};
return $result;
}
?>
