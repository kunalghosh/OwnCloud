<?php
//include("files_fileSync.php"); //already included

/*
function file_load($filename, &$ARRAY) {
	#lg("=====LOAD fn: $filename, count: " . count($ARRAY));
	$ff = fopen($filename, "r");
	$fc = fread($ff, filesize($filename));
	#lg("filesize = " . filesize($filename));
	#lg("all: $fc :lla");
	fclose($ff);
	$fA = explode("\n", $fc);
	foreach ($fA as $fs) { //paramname, paramvalue
		list($pn, $pv) = explode("=", $fs, 2);
		$ARRAY[strtolower(trim($pn))] = trim($pv);
	};
};

function file_save($filename, $ARRAY) {
	#lg("=====SAVE fn: $filename, count: " . count($ARRAY));
	#lg("all:");
	$ff = fopen($filename, "w");
	foreach ($ARRAY as $pn => $pv) { //paramname, paramvalue
		fwrite($ff, "$pn=$pv\r\n");
	#	lg("$pn=$pv");
	};
	fclose($ff);
	#lg(":lla");
};
*/

function get_response ($i,$source,$cmdref,$mesgid) {
    global $appName;
$get_response = "<Results><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Meta><Type xmlns='syncml:metinf'>application/vnd.syncml-devinf+xml</Type></Meta><Item><Source><LocURI>./devinf11</LocURI></Source><Data><DevInf xmlns=\"syncml:devinf\"><Man>Knox County</Man><Mod>Knox County SyncML Pro</Mod><OEM>Knox County</OEM><DevID>lssync001</DevID><DevTyp>Server</DevTyp><DataStore><SourceRef>./contacts</SourceRef><Rx-Pref><CTType>text/x-vcard</CTType><VerCT>2.1</VerCT></Rx-Pref><Rx><CTType>text/vcard</CTType><VerCT>3.0</VerCT></Rx><Tx-Pref><CTType>text/x-vcard</CTType><VerCT>2.1</VerCT></Tx-Pref><Tx><CTType>text/vcard</CTType><VerCT>3.0</VerCT></Tx><SyncCap><SyncType>1</SyncType><SyncType>2</SyncType><SyncType>3</SyncType><SyncType>4</SyncType><SyncType>5</SyncType><SyncType>6</SyncType></SyncCap></DataStore><CTCap><CTType>text/x-vcard</CTType><PropName>BEGIN</PropName><ValEnum>VCARD</ValEnum><PropName>END</PropName><ValEnum>VCARD</ValEnum><PropName>VERSION</PropName><ValEnum>2.1</ValEnum><PropName>N</PropName><PropName>FN</PropName><PropName>TITLE</PropName><PropName>ORG</PropName><PropName>CATEGORIES</PropName><PropName>CLASS</PropName><PropName>TEL</PropName><PropName>EMAIL</PropName><PropName>ADR</PropName><PropName>NOTE</PropName><CTType>text/vcard</CTType><PropName>BEGIN</PropName><ValEnum>VCARD</ValEnum><PropName>END</PropName><ValEnum>VCARD</ValEnum><PropName>VERSION</PropName><ValEnum>3.0</ValEnum><PropName>N</PropName><PropName>FN</PropName><PropName>TITLE</PropName><PropName>ORG</PropName><PropName>CATEGORIES</PropName><PropName>CLASS</PropName><PropName>TEL</PropName><PropName>EMAIL</PropName><PropName>ADR</PropName><PropName>NOTE</PropName></CTCap></DevInf></Data></Item></Results>\n";
return $get_response;
}

function put_response($i,$loc_cli,$loc_srv,$cmdref,$mesgid,$auth,$datas) {
        global $appName;
	$put_response = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Put</Cmd><SourceRef>$loc_cli</SourceRef>";
	if ($auth) {
		global $user_dir;
		#$s_dir = $user_dir . "/" . $loc_srv;
		$s_dir = $user_dir;
		$f_state = $user_dir . "/" . $loc_srv . ".state";
		#lg("userdir: $user_dir; sdir: $s_dir; last: $f_state");
		if (!OC_Filesystem::is_dir($s_dir)) OC_Filesystem::mkdir($s_dir);
		#global $STATE;
		#file_load($f_state, $STATE);
		$ff = OC_Filesystem::fopen("$loc_cli", "w");
		fwrite($ff, $datas);
		fclose($ff);
		OC_Log::write( $appName,"file $loc_cli written in $user_dir",  OC_Log::DEBUG);
		$put_response .= "<Data>200</Data></Status>\n";
		#lg("Status for put is 200 (ok)");
		//if not authenticated, response code should be 401;
	} else {
		$put_response .= "<Data>401</Data></Status>\n";
		OC_Log::write( $appName,"Status for put is 401 (unauthenticated)",  OC_Log::DEBUG);
	};
return $put_response;
}

function alert_response($i,$loc_cli,$loc_srv,$cmdref,$mesgid,$auth,$type,$rlast,$rnext,$source) {
        global $appName;
	if ($auth) {
		global $user_dir;
		#$s_dir = $user_dir . "/" . $loc_srv;
		$s_dir = $user_dir;
		$f_state = $user_dir . "/" . $loc_srv . "_" . $source . ".state";
		OC_Log::write( $appName,"userdir: $user_dir; sdir: $s_dir; last: $f_state",  OC_Log::DEBUG);
		if (! OC_Filesystem::is_dir($s_dir)) OC_Filesystem::mkdir($s_dir);
		#global $STATE; #what for?.. commented out
		//if local last = remote_last then do 200 (normal sync)
		//otherwise do 508 (slow sync)
		$start = microtime(true);
		if (in_array($type, Array("201", "203", "205"))) {
			OC_Log::write( $appName,"slow sync requested -> discarding state",  OC_Log::DEBUG);
			OC_Filesystem::unlink($f_state); #slow sync -> discard state
		};
		unset($STATE);
		$STATE = Array();
		file_load($f_state, $STATE);
		$end = microtime(true);
		OC_Log::write( $appName,"status file loaded in " . ($end - $start) . "ms",  OC_Log::DEBUG);
		//last/next, local/remote, old/new - 3 dimensions, 8 variables
		OC_Log::write( $appName,"preparing anchors",  OC_Log::DEBUG);
		$oloclast = $STATE["new_local_last"];
		$olocnext = $STATE["new_local_next"];
		$oremlast = $STATE["new_remote_last"];
		$oremnext = $STATE["new_remote_next"];
		if (empty($oloclast)) $oloclast = 0;
		if (empty($olocnext)) $olocnext = 0;
		if (empty($oremlast)) $oremlast = 0;
		if (empty($oremnext)) $oremnext = 0;
		$nloclast = $olocnext;
		$nlocnext = date('Ymd\THis'); #"now" like 20101123T053617
		$nremlast = $rlast;
		$nremnext = $rnext;
		if (empty($nremlast)) $nremlast = 0;
		if (empty($nremnext)) $nremnext = 0;
		#
		#$start = microtime(true);
		file_save($f_state, $STATE);
		#$end = microtime(true);
		#lg("status file saved in " . ($end - $start) . "ms");
		OC_Log::write( $appName,"remote = $nremlast; local = $oremnext",  OC_Log::DEBUG);
		if (($nremlast == $oremnext) AND ($nremlast != "0")) {
			$statusdata = "200"; #normal sync
			#$type = "200"; #Hangs devices
			#201???
			OC_Log::write( $appName,"anchors match (normal sync pending)",  OC_Log::DEBUG);
		} else {
			$statusdata = "508"; #slow sync
			//$type = "201";
			#$type = $type + 1; #that's wrong when type==201 already
			if (in_array($type, Array("200", "202", "204"))) {
				$type = $type + 1;
			};
			OC_Log::write( $appName,"anchors mismatch (slow sync pending)",  OC_Log::DEBUG);
		};
		if (in_array($type, Array("201", "203", "205"))) {
		//if ($type == "201") {
			OC_Log::write( $appName,"slow sync required (anchors mismatch) -> discarding state",  OC_Log::DEBUG);
			unlink($f_state); #slow sync -> discard state
			#mapping will be lost!
		};
		unset($STATE);
		$STATE = Array();
		file_load($f_state, $STATE);
		$STATE["new_remote_last"] = $nremlast;
		$STATE["new_remote_next"] = $nremnext;
		$STATE["new_local_last"] = $nloclast;
		$STATE["new_local_next"] = $nlocnext;
		$STATE["old_remote_last"] = $oremlast;
		$STATE["old_remote_next"] = $oremnext;
		$STATE["old_local_last"] = $oloclast;
		$STATE["old_local_next"] = $olocnext;
		$STATE["type"] = $type; #in Sync we need to know it
		OC_Log::write( $appName,"anchors ready. saving state",  OC_Log::DEBUG);
		file_save($f_state, $STATE);

		$alert_response = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Alert</Cmd><TargetRef>$loc_srv</TargetRef><SourceRef>$loc_cli</SourceRef><Data>$statusdata</Data>" . "<Item><Data><Anchor xmlns=\"syncml:metinf\"><Next>$nremnext</Next></Anchor></Data></Item>" . "</Status>";
		$alert_response .= "<Alert><CmdID>".($i+1)."</CmdID><Item><Target><LocURI>$loc_cli</LocURI></Target><Source><LocURI>$loc_srv</LocURI></Source><Meta><Anchor xmlns=\"syncml:metinf\"><Last>$nloclast</Last><Next>$nlocnext</Next></Anchor></Meta></Item><Data>$type</Data></Alert>\n";
		OC_Log::write( $appName,"Answer = $statusdata; Sync type = $type",  OC_Log::DEBUG);
	} else {
		$alert_response = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Alert</Cmd><TargetRef>$loc_srv</TargetRef><SourceRef>$loc_cli</SourceRef><Data>401</Data></Status>\n";
		OC_Log::write( $appName,"Answer = 401",  OC_Log::DEBUG);
	};
return $alert_response;
}

function status_response($i,$comdref,$mesgid) {
        global $appName;
	
	$stat = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Alert</Cmd><TargetRef>./contacts</TargetRef><SourceRef>contacts</SourceRef><Data>200</Data><Item><Data><Anchor xmlns=\"syncml:metinf\"><Last>20050629T132132Z</Last><Next>20050629T154536Z</Next></Anchor></Data></Item></Status>\n";
	//don't return anything
}

/////////////////////////////////////////////////////////////////
/*
function write_item($s_dir,$item,$data,$source) {
if (! is_dir($s_dir)) mkdir($s_dir);
$fitem = fopen($s_dir . "/" . $item, "w");
fwrite($fitem, $data);
fclose($fitem);
$f_state = $s_dir . "_" . $source . ".state";
#lg("fstate: $f_state");
$STATE = Array();
file_load($f_state, $STATE); #it can be faster to do append
$STATE["hash_$item"] = md5($data);
#lg("item: $item, md5: " . $STATE["hash_$item"]);
file_save($f_state, $STATE);
#DONE:don't forget to do unset($STATE[$item]) in Map function;
}

function remove_item($s_dir,$item,$source) {
if (! is_dir($s_dir)) mkdir($s_dir);
#lg("removing " . $s_dir . "/" . $item . ". is file" . (is_file($s_dir . "/" . $item) ? "true" : "false"));
if (! unlink($s_dir . "/" . $item)) lg("unable to remove $s_dir/$item");
$STATE = Array();
$f_state = $s_dir . "_" . $source . ".state";
file_load($f_state, $STATE);
unset($STATE["hash_$item"]);
file_save($f_state, $STATE);
}
*/ //moved to files_fileSync.php
/////////////////////////////////////////////////////////////////

function add_contact($i,$cmdref,$mesgid,$auth,$lcli,$lsrv,$item,$data,$source,$MoreData) {
    global $appName;
#same as replace_contact
#actually, documentation says that devices can use Replace instead of Add and they do.
global $user_dir;	
if ($auth) {
	$result = "201"; #If the command completed successfully, then the (201) Item added exception condition is created by the command. (c) syncml_sync_represent_v11_20020215
	#$s_dir = $user_dir . "/" . $lsrv;
	$s_dir = $user_dir;
	if ($MoreData == 1)
	{
		OC_Log::write( $appName,"incomplete item. buffering in $item.tmp",  OC_Log::DEBUG);
		$ftmp = OC_Filesystem::fopen($item . ".tmp", "a");
		fwrite($ftmp, $data);
		fclose($ftmp);
		$result = "213"; //"Chunked item accepted and buffered" (c)
		//Damn typo in spec's
	} else {
		$tmpfn = "/" . $item . ".tmp";
		if (OC_Filesystem::file_exists($tmpfn))
		{
			OC_Log::write( $appName,"item concatenated from $item.tmp",  OC_Log::DEBUG);
			$ftmp = OC_Filesystem::fopen($tmpfn, "r");
			$data = fread($ftmp, filesize($tmpfn)) . $data;
			fclose($ftmp);
			if (! unlink($tmpfn)) OC_Log::write( $appName,"error: $item.tmp is not deleted!",  OC_Log::DEBUG);
			//there should be some size check
			#proceed normal operation (as if there were no split)
		};
		#if (file_exists($s_dir . "/" . $item)) {
		if (exists_item($s_dir,$item,$source)) {
			$result = "418"; #already exists
			OC_Log::write( $appName,$s_dir . "/" . $item . " already exists",  OC_Log::DEBUG);
		} else {
			new_item($s_dir,$item,$data,$source);
		};
	};
} else {
	$result = "401";
};
$add = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Add</Cmd><SourceRef>$lcli</SourceRef><Data>$result</Data></Status>\n";
return $add;
}

function replace_contact($i,$cmdref,$mesgid,$auth,$lcli,$lsrv,$item,$data,$source,$MoreData) {
    global $appName;
// replace_contact($i,$svalue->CmdID,$mesgid,$authenticated,$lcli,$lsrv,$svalue->Item->Source->LocURI);	
// replace_contact($i,$scmdid,$mesgid,$authenticated,$lcli,$lsrv,$slcli,$sdata);
global $user_dir;	
if ($auth) {
	OC_Log::write( $appName,"FUNCTION: REPLACE_CONTACT in files_fileSync.php item = $item",  OC_Log::DEBUG);
	$result = "200";
	#$s_dir = $user_dir . "/" . $lsrv;
	$s_dir = $user_dir;
	$f_state = $s_dir . "_" . $source . ".state";
	if ($MoreData == 1)
	{
		OC_Log::write( $appName,"incomplete item. buffering in $item.tmp",  OC_Log::DEBUG);
		$ftmp = OC_Filesystem::fopen("/" . $item . ".tmp", "a");
		fwrite($ftmp, $data);
		fclose($ftmp);
		$result = "213"; //"Chunked item accepted and buffered" (c)
		//Damn typo in spec's
	} else {
		$tmpfn = $s_dir . "/" . $item . ".tmp";
		OC_Log::write( $appName,"REPLACE CONTACT ---- $tmpfn",  OC_Log::DEBUG);
		if (OC_Filesystem::file_exists($tmpfn))
		{
			OC_Log::write( $appName,"item concatenated from $item.tmp",  OC_Log::DEBUG);
			$ftmp = OC_Filesystem::fopen($tmpfn, "r");
			$data = fread($ftmp, OC_Filesystem::filesize($tmpfn)) . $data;
			fclose($ftmp);
			if (! OC_Filesystem::unlink($tmpfn)) OC_Log::write( $appName,"error: $item.tmp is not deleted!",  OC_Log::DEBUG);
			//there should be some size check
			#proceed normal operation (as if there were no split)
		};
		#code below till "if" is useless in case of:
		#a)state file non-exists
		#b)item not exists (Replace used as Add)
		#c)no hash found
		#BUT: hash can exist without item!
		#wait.. it shouldn't.
		//$STATE = Array();
		//file_load($f_state, $STATE); #it loaded and then discarded
		//$saved_hash = $STATE["hash_$item"];
		$saved_hash = get_hash($s_dir,$item,$source);
		unset($STATE);
		//if (file_exists("$s_dir/$item")) $file_hash = md5_file("$s_dir/$item");
		if (exists_item($s_dir,$item,$source)) $file_hash = md5(get_item($s_dir,$item,$source));
		if ((! empty($saved_hash)) AND (! empty($file_hash)) AND ($saved_hash != $file_hash)) { #item changed
			OC_Log::write( $appName,"saved: $saved_hash, file: $file_hash. Item changed. Conflict",  OC_Log::DEBUG);
			$result = conflict_solve(1,$s_dir,$item,$data,$source);
			#conflicts.php
		} else {
			//if (file_exists("$s_dir/$item")) {
			if (exists_item($s_dir,$item,$source)) {
				#not changed - real replace
				write_item($s_dir,$item,$data,$source);
			} else {
				#not exists - add
				new_item($s_dir,$item,$data,$source);
			};

		};
	};
} else {
	$result = "401";
};
$replace = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Replace</Cmd><SourceRef>$item</SourceRef><Data>$result</Data></Status>\n";
return $replace;
}

function delete_contact($i,$cmdref,$mesgid,$auth,$lcli,$lsrv,$item,$source) {
    global $appName;
//($i,$scmdid,$mesgid,$authenticated,$lcli,$lsrv,$slcli)
global $user_dir;	
if ($auth) {
	#lg("LOG DELETE CONTACTS: cmdref = $cmdref mesgid = $mesgid auth = $auth lcli = $lcli lsrv = $lsrv");
	$result = "200";
	#$s_dir = $user_dir . "/" . $lsrv;
	$s_dir = $user_dir;
	//if (! file_exists($s_dir . "/" . $item)) {
	if (! exists_item($s_dir,$item,$source)) {
		$result = "211"; #Item not deleted
		OC_Log::write( $appName,"DELETING :" . $s_dir . "/" . $item . " doesn't exist ",  OC_Log::DEBUG);
	} else {
		remove_item($s_dir,$item,$source);
	};
} else {
	$result = "401";
};
$delete = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Delete</Cmd><SourceRef>$lcli</SourceRef><Data>$result</Data></Status>\n";
return $delete;
}

function map_response($i,$cmdref,$mesgid,$auth,$lcli,$lsrv,$mlcli,$mlsrv,$source) {
    global $appName;
//$i,$cmdid,$mesgid,$authenticated,$lcli,$lsrv,$mlcli,$mlsrv
global $user_dir;	
if ($auth) {
	rename_item($s_dir,$mlsrv,$mlcli,$source);
	/*
	$s_dir = $user_dir . "/" . $lsrv;
	#lg("userdir: $user_dir; sdir: $s_dir");
	if (! is_dir($s_dir)) mkdir($s_dir);
	lg("renaming $mlsrv to $mlcli in $s_dir");
	rename($s_dir . "/" . $mlsrv, $s_dir . "/" . $mlcli);
	$f_state = $s_dir . "_" . $source . ".state";
	#lg($f_state);
	$STATE = Array();
	file_load($f_state, $STATE);
	$STATE["hash_$mlcli"] = $STATE["hash_$mlsrv"];
	unset($STATE["hash_$mlsrv"]);
	file_save($f_state, $STATE);
	*/
	$map = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Map</Cmd><SourceRef>$lcli</SourceRef><Data>200</Data></Status>\n";
} else {
	$map = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>$cmdref</CmdRef><Cmd>Map</Cmd><SourceRef>$lcli</SourceRef><Data>401</Data></Status>\n";
}
return $map;
};

?>
