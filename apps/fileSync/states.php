<?php

function file_load($filename, &$ARRAY) {
        global $appName;
	#lg("=====LOAD fn: $filename, count: " . count($ARRAY));
	/*
	clearstatcache(); #2 hours gone to achieve this =/
	$ff = fopen($filename, "r");
	$fc = fread($ff, filesize($filename));
	fclose($ff);
	 */
	$fc = OC_Filesystem::file_get_contents($filename);
	$fc = $fc . "\n"; #last line wont be read without this
	OC_Log::write( $appName,"filesize = " . OC_Filesystem::filesize($filename),  OC_Log::DEBUG);
	OC_Log::write( $appName,"all: $fc :lla",  OC_Log::DEBUG);
	$fA = explode("\n", $fc);
	foreach ($fA as $fs) { //paramname, paramvalue
		if (empty($fs)) continue;
		list($pn, $pv) = explode("=", $fs, 2);
		#$ARRAY[strtolower(trim($pn))] = trim($pv);
		$ARRAY[trim($pn)] = trim($pv);#removed the strtolower() so that filenames after sync retain their case. 
	};
};

function file_save($filename, $ARRAY) {
        global $appName;
	OC_Log::write( $appName,"=====SAVE fn: $filename, count: " . count($ARRAY),  OC_Log::DEBUG);
	OC_Log::write( $appName,"all:",  OC_Log::DEBUG);
	$ff = OC_Filesystem::fopen($filename, "w");
	foreach ($ARRAY as $pn => $pv) { //paramname, paramvalue
		if (empty($pn)) continue;
		#if (empty($pv)) continue;
		fwrite($ff, "$pn=$pv\r\n");
		OC_Log::write( $appName,"$pn=$pv",  OC_Log::DEBUG);
	};
	fclose($ff);
	#lg(":lla");
};

?>
