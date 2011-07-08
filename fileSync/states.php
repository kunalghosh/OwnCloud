<?php

function file_load($filename, &$ARRAY) {
	#lg("=====LOAD fn: $filename, count: " . count($ARRAY));
	/*
	clearstatcache(); #2 hours gone to achieve this =/
	$ff = fopen($filename, "r");
	$fc = fread($ff, filesize($filename));
	fclose($ff);
	 */
	$fc = file_get_contents($filename);
	$fc = $fc . "\n"; #last line wont be read without this
	#lg("filesize = " . filesize($filename));
	#lg("all: $fc :lla");
	$fA = explode("\n", $fc);
	foreach ($fA as $fs) { //paramname, paramvalue
		if (empty($fs)) continue;
		list($pn, $pv) = explode("=", $fs, 2);
		$ARRAY[strtolower(trim($pn))] = trim($pv);
	};
};

function file_save($filename, $ARRAY) {
	#lg("=====SAVE fn: $filename, count: " . count($ARRAY));
	#lg("all:");
	$ff = fopen($filename, "w");
	foreach ($ARRAY as $pn => $pv) { //paramname, paramvalue
		if (empty($pn)) continue;
		#if (empty($pv)) continue;
		fwrite($ff, "$pn=$pv\r\n");
	#	lg("$pn=$pv");
	};
	fclose($ff);
	#lg(":lla");
};

?>
