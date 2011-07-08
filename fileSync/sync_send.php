<?php
# this file should contain only 1 function - do_sync (and it's helpers / subfunctions).
# do sync should create <Sync>...</Sync> content and return it as a string.

function do_sync_twoway_slow(&$i,$s_dir,$source) {
#slow two-way	
$twowayslow = "";
$twowayslow .= "";

#There is a problem..
#How not to send files, already sent to us?
#At this point we'll do usual sync
#is client deletes all at this point? seems to be not.. then how?..
$twowayslow = do_sync_twoway($i,$s_dir,$source);
#In practise, the slow sync means that the client sends all its data in a database to the server and the server does the sync analysis (field-by-field) for this data and the data in the server. After the sync analysis, the server returns all needed modifications back to the client. Also, the client returns the Map items for all data items, which were added by the server. (c) syncml_sync_protocol_v11_20020215.pdf
#So.. I want to try..
#1. descard status file early (when alert recieved)
#2. recieve client's data
# so, status file will contain data about client's records
# but, if sync fails - how to rollback?.. easily.
# after sync there will be valid state file
#Yep, this will work..
#So, no more code here - all code will be in index.php or get_response.php
#Later this function will be eliminated.

return $twowayslow;
}

function do_sync_twoway(&$i,$s_dir,$source) {
#two way differential
$twoway = "";

#1.Get files in dir s_dir
#2.Get all hashes in STATE
#3.Checking hashes and excluding equal
#4.Ask to remove hashes without files (and remove hashes)
#5.Ask to add files without hashes (+ hash & add them)
#6.Ask to replace files, whose hashes changed
#
#Maybe all this in differential functions?
#Then slow sync wouldn't be difficult to realise..
#..but it shouldn't be dificult in any way - just send all

#1.Get files in dir s_dir
$SFiles = Array();
/*
$fcnt = 0;
$SDir = opendir($s_dir);
while (false !== ($file = readdir($SDir))) {
	if ($file != "." && $file != "..") {
		$SFiles[$file] = md5_file($s_dir . "/" . $file);
		$fcnt++;
	};
};
closedir($SDIR);
lg("found $fcnt files");
*/

list_items($s_dir,$SFiles,$source);

#2.Get all hashes in STATE
$CFiles = Array();
/*
$fcnt = 0;
foreach ($STATE as $file => $hash) {
	if (strpos($file, "hash_") !== false) {
		$CFiles[substr($file, 5)] = $hash;
		$fcnt++;
	};
};
#lg("count: " . count($STATE));
lg("found $fcnt hashes");
*/
list_hashes($s_dir,$CFiles,$source);


#3.Checking hashes and excluding equal
$fcnt = 0;
$DFiles = Array(); #Differd
foreach ($SFiles as $file => $hash) {
	if (array_key_exists($file, $CFiles)) {
		#filenames are case sensitive!
		if ($hash != $CFiles[$file]) {
			$DFiles[$file] = $hash;
			# Hash changed => contents differs => to replace;
		};
		unset($SFiles[$file]); #do not add
		unset($CFiles[$file]); # or delete this element
		$fcnt++;
	};
};
$toadd = count($SFiles);
$todel = count($CFiles);
$torep = count($DFiles);
lg("$fcnt files with hashes (unchanged items)");
lg($toadd . " new items to be sent"); #files wihout hashes
lg($todel . " old items to be asked to remove"); #hashes wihout files
lg($torep . " old items to replaced"); #hashes wihout files
lg("Number of Changes = " . ($toadd + $todel + $torep)); #+ $torep (replace);

#DONE: Send '<NumberOfChanges>X</NumberOfChanges>'
$twoway .= "<NumberOfChanges>" . ($toadd + $todel + $torep) . "</NumberOfChanges>";

#4.Ask to remove hashes without files (and remove hashes)
foreach ($CFiles as $file => $hash) {
	lg("asking to delete $file");
	#$twoway .= "<Delete><CmdID>$i</CmdID><Item><Source><LocURI>$file</LocURI></Source></Item></Delete>";
	$twoway .= "<Delete><CmdID>$i</CmdID><Item><Target><LocURI>$file</LocURI></Target></Item></Delete>"; #Target/Source problem maybe here <<<<<<<<<<<<<<<<
	remove_hash($s_dir,$file,$source);
	remove_mapping($s_dir,$file,$source);
	//unset($STATE["hash_" . $file]);
	$i++; #CmdID
};

#5.Ask to add files without hashes (+ hash & add them)
foreach ($SFiles as $file => $hash) {
	$itemdata = file_get_contents($s_dir . "/" . $file);
	#DONE: Make shure that $itemdata is free from "]]>"
	$itemdata = str_replace("]]>", "]]]]><![CDATA[>", $itemdata);
	#See http://en.wikipedia.org/wiki/CDATA
	lg("asking to add $file");
	$twoway .= "<Add><CmdID>$i</CmdID>" . /*/ "<Meta><Type xmlns=\"syncml:metinf\">text/plain</Type></Meta>" ./**/ "<Item><Source><LocURI>$file</LocURI></Source><Target><LocURI>$file</LocURI></Target><Data><![CDATA[" . $itemdata . "]]></Data></Item></Add>";
	//$STATE["hash_" . $file] = $hash;
	write_hash($s_dir,$file,$source,$hash);
	$i++; #CmdID
};

#6.Ask to replace files, whose hashes changed
#DONE: do comparation for Replace elemets
 #for now it's removing and adding changed element
foreach ($DFiles as $file => $hash) {
	$itemdata = file_get_contents($s_dir . "/" . $file);
	$itemdata = str_replace("]]>", "]]]]><![CDATA[>", $itemdata);
	lg("asking to replace $file");
	$twoway .= "<Replace><CmdID>$i</CmdID>" . /*/ "<Meta><Type xmlns=\"syncml:metinf\">text/plain</Type></Meta>" ./**/ "<Item><Target><LocURI>$file</LocURI></Target><Data><![CDATA[" . $itemdata . "]]></Data></Item></Replace>";
	//$STATE["hash_" . $file] = $hash;
	write_hash($s_dir,$file,$source,$hash);
	$i++; #CmdID
};

$twoway .= ""; #i forgot what for i prepared it..

//file_save($f_state, $STATE);
//lg("state saved to $f_state");

return $twoway;
}

function do_sync(&$i,$mesgid,$auth,$lcli,$lsrv,$source) {
#lg("do_sync(\$i,\$mesgid,\$auth,\$lcli,\$lsrv)");
lg("do_sync(cli->$lcli, srv->$lsrv)");
global $user_dir;	
if ($auth) {
	$s_dir = $user_dir . "/" . $lsrv;
	#lg("userdir: $user_dir; sdir: $s_dir");
	if (! is_dir($s_dir)) mkdir($s_dir);
	$sync = "<Sync><CmdID>$i</CmdID><Target><LocURI>$lcli</LocURI></Target><Source><LocURI>$lsrv</LocURI></Source>";
	$i++;
	$f_state = $s_dir . "_" . $source . ".state";
	#lg("f_state = $f_state");
#  ob_start();
#  var_dump($STATE);
#  $dump = ob_get_clean();
#  lg($dump);
	$STATE = Array();
	file_load($f_state, $STATE);
	$type = $STATE["type"];
	unset($STATE);
	#unset($STATE["$item"]);
	//file_save($f_state, $STATE);
	switch ($type) {
		case "200":
			//two-way
			$sync .= do_sync_twoway($i,$s_dir,$source);
			break;
		case "201":
			//slow two-way
			$sync .= do_sync_twoway_slow($i,$s_dir,$source);
			break;
		case "202":
			//202 - CtS
			$sync .= ""; #just recieving
			break;
		case "203":
			//203 - CtS slow (refresh)
			$sync .= ""; #just recieving
			break;
		case "204":
			//204 - StC
			$sync .= do_sync_twoway($i,$s_dir,$source); #dummy
			break;
		case "205":
			//205 - StC slow (refresh)
			$sync .= do_sync_twoway_slow($i,$s_dir,$source); #dummy
			break;
		default:
			break;
	};
	$sync .= "</Sync>";
} else {
	#$sync = "<Sync><CmdID>$i</CmdID><Target><LocURI>$lcli</LocURI></Target><Source><LocURI>$lsrv</LocURI></Source></Sync>"; //empty sync
	$sync = ""; //empty sync
	lg("User unauthenticated, but requested sync. That's weird. Should we send <Sync> at all? Nothing has been sent.");
	#$sync = "";
};
return $sync;
}
?>
