<?php
//ini_set('display_errors','Off');
error_reporting(0);

include("logging.php");
include("sync_send.php");
include("conflicts.php");
include("states.php");
include("files.php");
include("get_response.php");
include("config.php");
parse_config(); #config.php

lg('working. $Id$'); #what is the use of this ?

//Lets open our log file for debugging purposes
if ($keep_exchange > 0) {
	$handler = fopen("$base_dir/progress.xml", "a+");
	$hand_rq = fopen("$base_dir/in.xml", "w");
	$hand_rs = fopen("$base_dir/out.xml", "w");
};

// assign POST data to variable (comment out for debugging)
$post_data = $HTTP_RAW_POST_DATA;

$ct = $_SERVER["CONTENT_TYPE"];

if ($ct == "application/vnd.syncml+wbxml") { $input_type = "wbxml"; }
	else if ($ct == "application/vnd.syncml+xml") {$input_type = "xml"; }
	else {
		$input_type = "unknown"; //pretend that it is xml
		//echo "<html>I only speak SyncML.</html>";
		exit();
	};
lg("Recognized type: $input_type");

// Extract data
if ($input_type == 'wbxml') {
	$xmlsh = wbxml_decode($post_data);
	header("Content-Type: application/vnd.syncml+wbxml; charset=UTF-8");
} else {
	$xmlsh = $post_data;
	header("Content-Type: application/vnd.syncml+xml; charset=UTF-8");
}
if ($keep_exchange > 0) {
	fwrite($handler, "\nC-\>S\n");
	fwrite($handler, $xmlsh);
	fwrite($handler, "\nC-\>S\n");
	fwrite($hand_rq, $xmlsh);
	fflush($hand_rq);
};

// Load our response into a simplexml object
$xmls = simplexml_load_string($xmlsh);

$synchdr = $xmls->SyncHdr;
$syncbod = $xmls->SyncBody;
$sessid = $synchdr->SessionID;
$mesgid = $synchdr->MsgID;
$source = $synchdr->Source->LocURI;
$source_s = str_replace(str_split(':./\\&<>?*[]|'), "", $source);
$target = $synchdr->Target->LocURI;
$auth64 = $synchdr->Cred->Data;
if (($mesgid == 1) and ($clear_log > 0)) {
	if ($do_log > 0) unlink("$base_dir/log.txt");
	lg("Log cleared due to 1st message");
};

lg("Session: $sessid Message: $mesgid");
lg("Source: $source");
lg("Target: $target");
$auth = base64_decode($auth64);
$A = explode(':', $auth, 2);
$user = $A[0];
$pass = $A[1];
unset($A);
lg("user: $user pass: $pass");
/////////////////////////////////////////////////parsing user profile
if (empty($user)) {
	//$user = "anonymous";
	##search_session($sessid) -> $user
	$sess_f = fopen($base_dir . '/' . "sessions", "r");
	$sess_p = fread($sess_f, filesize($base_dir . '/' . "sessions"));
	lg("'$sess_p'");
	fclose($sess_f);
	$sess_A = explode("\n", $sess_p);
	foreach ($sess_A as $value) {
		list($par_name, $par_value) = explode("=", $value, 2);
		$par_name = trim($par_name);
		$par_value = trim($par_value);
		lg("$sessid == $par_value > $user = $par_name");
		if ($sessid == $par_value) $user = $par_name;
		#$SESS[strtolower(trim($par_value))] = trim($par_name);
	};
	#$user = $SESS[$sessid];
	##/search_session()
	if (! empty($user)) lg("for session $sessid found user $user.");
	if (empty($user)) $user = "anonymous";
	#unset($sess_f,$sess_p,$sess_A,$par_name,$par_value,$SESS);
	unset($sess_f,$sess_p,$sess_A,$par_name,$par_value);
};
$user_dir = $base_dir . '/' . $user;
if ((! is_dir($user_dir)) && ($unrestricted > 0)) { //DONE:and user creation is allowed
	##mk_user($user_dir, $pass)
	mkdir($user_dir);
	lg("dir created: $user_dir");
	$user_f = fopen($user_dir . '/' . "profile", "w");
	fwrite($user_f, "password=$pass\r\n");
	fclose($user_f);
	##/mk_user($user_dir, $pass)
};
else{
	lg("index.php - dir not created already exists - user_dir = $user_dir");
}
if (! ($unrestricted > 0)) lg("user $user tried to login to restricted server");
lg("creating USER array");
#$USER = array();
#$USER["password"] = "invalid"; //for empty user to unauthenticate

# replace with file_load from get_responce.php
lg("user profile: " . $user_dir . '/' . "profile");
file_load("$user_dir/profile", $USER);

function user_profile_save()
{
	global $USER;
	global $user_dir;
	$user_f = fopen($user_dir . '/' . "profile", "w");
	foreach ($USER as $key => $val) {
		fwrite($user_f, "$key=$val\r\n");
		lg("$key=$val");
	};
	fclose($user_f);
	lg("user_profile_save");
};

function user_profile_add($key, $value)
{
	global $USER;
	global $user_dir;
	if (array_key_exists($key, $USER))
	{
		lg("key exists");
		$USER[strtolower(trim($key))] = trim($value);
		lg("USER[" . strtolower(trim($key)) . "] = trim($value)");
		lg($USER[strtolower(trim($key))] . "=" . trim($value));
		user_profile_save();		//DONE:writeit
	} else {
		lg("key not exists");
		$user_f = fopen($user_dir . '/' . "profile", "a");
		lg("u: $user_dir; $key=$value.");
		fwrite($user_f, "$key=$value\r\n");
		fclose($user_f);
		$USER[strtolower(trim($key))] = trim($value);
	};
};
function user_profile_get($key) {
	global $USER;
	unset($value);
	lg(">>>>$key>>>>" . $USER[$key]);
	if (array_key_exists($key, $USER)) $value = $USER[$key];
	return $value;
}

/////////////////////////////////////////////////////////////////////
lg("user profile parsed");
//$USER["password"] - password
//$USER["session"] - SessionID
//$USER["passkey"] - Server provided key

//get passkey from header:
if (array_key_exists("passkey", $_GET)) $passkey = $_GET["passkey"];
$authenticated = (bool)(
	(
		($sessid == user_profile_get("session"))
		and ($passkey == user_profile_get("passkey"))
	)
	or ($pass == user_profile_get("password"))
);
if ($authenticated) {
	lg("authenticated: $authenticated");
} else {
	lg("unauhenticated $authenticated");
};
//now user is authenticated
if ($authenticated) {
	//Transaction start should be started here
	//TODO: make a copy of userdir till the end of transaction (and lock it)
	lg(">>>>" . $USER["session"]);
	user_profile_add("session", $sessid);
	lg(">>>>" . $USER["session"]);
	if ( (! isset($passkey)) OR ($passkey == "")) {
		$passkey = $_SERVER["UNIQUE_ID"];
		//as a source of random data. 24chars
		user_profile_add("passkey", $passkey);
	}
	$SESSIONS = Array();
	file_load($base_dir . "/sessions", $SESSIONS);
	$SESSIONS[$user] = "$sessid";
	file_save($base_dir . "/sessions", $SESSIONS);
	#this will keep sessions file clean, but makes a race conditions situation. Sessions can be lost, when something happens between load and save.
	#TODO: make sessions per-user. Maybe parse all users?..

};

$i = 1;
//header("Content-Type: application/vnd.syncml+xml");
//#header("Accept-Charset: UTF-8");

$vera = "1"; //protocol version
$verb = "1";
$ver = "$vera.$verb";
lg("acting like ver $ver");
if (array_key_exists("HTTPS", $_SERVER)) {
	$RespURI = $_SERVER["SCRIPT_URI"];
} else {
	$RespURI = "http://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["SCRIPT_NAME"];
};
$header = "<?xml version='1.0' encoding=\"UTF-8\"?>
<!DOCTYPE SyncML PUBLIC \"-//SYNCML//DTD SyncML $ver//EN\" \"http://www.openmobilealliance.org/tech/DTD/OMA-TS-SyncML_RepPro_DTD-V".$vera."_".$verb.".dtd\"><SyncML xmlns='SYNCML:SYNCML$ver'><SyncHdr><VerDTD>$ver</VerDTD><VerProto>SyncML/$ver</VerProto><SessionID>".$sessid."</SessionID><MsgID>".$mesgid."</MsgID><Target><LocURI>".$source."</LocURI></Target><Source><LocURI>".$target."</LocURI></Source><RespURI>" . $RespURI . "?passkey=$passkey</RespURI></SyncHdr><SyncBody>\n";
lg("header ready. passkey=$passkey");

$send = $header;
///*
#if ($synchdr->Cred->Data != "") {
$content = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>0</CmdRef><Cmd>SyncHdr</Cmd><TargetRef>$target</TargetRef><SourceRef>$source</SourceRef>";
if ($authenticated) {
	$content .= "<Data>212</Data></Status>";
	lg("Status for SyncHdr is 212 (authenticated)");
} else {
	$content .= "<Chal><Meta><Format xmlns=\"syncml:metinf\">b64</Format><Type xmlns=\"syncml:metinf\">syncml:auth-basic</Type></Meta></Chal><Data>407</Data></Status>";
	lg("Status for SyncHdr is 407 (unauthenticated)");
};
	$send .= $content;
	$i++;

lg("Parsing body");
#if (isset($syncbod->Final)) lg("Final present");
foreach($syncbod->children() as $key => $value) {
	$lcli = $value->Item->Source->LocURI;
	$lsrv = $value->Item->Target->LocURI;
	if (empty($lcli)) $lcli = $value->Item->Source;
	if (empty($lsrv)) $lsrv = $value->Item->Target;
	if (empty($lcli)) $lcli = $value->Source->LocURI;
	if (empty($lsrv)) $lsrv = $value->Target->LocURI;
	if (empty($lcli)) $lcli = $value->Source;
	if (empty($lsrv)) $lsrv = $value->Target;
	$cmdid = $value->CmdID;
	$data = $value->Data;
	$cmd = $value->Cmd;
	lg("Found $key: Cli=$lcli Srv=$lsrv Cmd=$cmdid Data=$data CmdRef=$cmd");
	switch ($key) {
		case "Put":
			#$send .= $put_response;
			$data = $value->Item->Data; #override
			$datat = $data->asXML();
			$datas = substr($datat, 6, -7); #remove '<Data>'..
			lg("datas: $datas");
			$send .= put_response($i,$lcli,$lsrv,$cmdid,$mesgid,$authenticated,$datas);
			$i++;
			break;
		case "Get":
			$gets = get_response($i,$lcli,$cmdid,$mesgid);
			$send .= $gets;
			$i++;
			break;
		case "Alert":
			$last = $value->Item->Meta->Anchor->Last;
			$next = $value->Item->Meta->Anchor->Next;
			$type = $value->Data;
			lg("Alert Last=$last; Next=$next; SourceX=$source_s");
			$alerts = alert_response($i,$lcli,$lsrv,$cmdid,$mesgid,$authenticated,$type,$last,$next,$source_s);
			$send .= $alerts;
			$i++;
			break;
		case "Status":
			$status = status_response($i,$cmdid,$mesgid);
			$send .= $status;
			//$i++; #since we don't responce - no need to do this
			break;
		case "Sync":
			$sync_re = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>".$cmdid."</CmdRef><Cmd>Sync</Cmd><TargetRef>$lsrv</TargetRef><SourceRef>$lcli</SourceRef><Data>200</Data></Status>";
			$i++;
			$send .= $sync_re;
			if (isset($value->NumberOfChanges)) lg("NumberOfChanges: " . $value->NumberOfChanges);
			foreach($value->children() as $skey => $svalue) {
				$scmdid = $svalue->CmdID;
				lg("Sync: $skey; src: $slcli");
				lg($svalue->children());
				if (in_array($skey, array("Add", "Replace", "Delete"))) {
					foreach ($svalue->children() as $sskey => $ssvalue) {
						if ($sskey == "Item") {
							$slcli = $ssvalue->Source->LocURI;
							$slsrv = $ssvalue->Target->LocURI;
							if (empty($slcli)) $slcli = $ssvalue->Source; #SyncLocClient
							if (empty($slsrv)) $slsrv = $ssvalue->Target; #SyncLocServer
							#solutions to get server location $slsrv incase it is empty from the previous step
							#Solution 1:
							#if (empty($slsrv) && !empty($slcli)) $slsrv = $slcli;#the following commented solution is better !
							#Solution 2: (IMHO gives a more accurate location because it uses previously specified location (directory) that the file must be present: $lsrv
							if(!empty($slcli)) $slcli_array = explode("/",$slcli);
							if (empty($slsrv) && !empty($slcli)) $slsrv = $lsrv . "/" . end($slcli_array);				
							lg("NEW SLSRV $slsrv");
							lg("Both Must have values slcli = $slcli slsrv = $slsrv ");#if slsrv is empty then the server doesnt know where to save the file :)
							$sdata = $ssvalue->Data;
							$MoreData = 0;
							lg("$key -> $skey -> Item[$slcli]");
							if (isset($ssvalue->MoreData)) $MoreData = 1;
							switch ($skey) {
								case "Add":
									$add = add_contact($i,$scmdid,$mesgid,$authenticated,$lcli,$lsrv,$slcli,$sdata,$source_s,$MoreData);
									$send .= $add;
									$i++;
									break;
								case "Replace":
									$replace = replace_contact($i,$scmdid,$mesgid,$authenticated,$lcli,$lsrv,$slcli,$sdata,$source_s,$MoreData);
									$send .= $replace;
									$i++;
									break;
								case "Delete":
									$delete = delete_contact($i,$scmdid,$mesgid,$authenticated,$lcli,$lsrv,$slcli,$source_s);
									$send .= $delete;
									$i++;
									break;
								default:
									break;
							}; //switch
						}; //if ($sskey == "Item")
					}; //foreach ($svalue->children()..
				}; //if (in_array($skey..
			}; //foreach($value->children()

			if (isset($syncbod->Final)) {
				lg("Final present. Doing Sync");
				$sync = do_sync($i,$mesgid,$authenticated,$lcli,$lsrv,$source_s);
				$send .= $sync;
			} else {
				lg("No Final. Not last part. Sync delayed");
				#$send .= "<Sync></Sync>";
				//$send .= "<Alert><CmdID>$i</CmdID><Data>222</Data><Item><Data>Please send more</Data></Item></Alert>"; //can be omitted by standad is there are any data to send (in our case - statuses)
				$i++;
			};
			#$i++; //do_sync must increment $i
			break;
		case "Map": #there can be multiple Maps (for different Syncs)
			foreach($value->children() as $skey => $svalue) {
			#there can be multiple MapItems (for different Items)
				if ($skey != "MapItem") continue;
				$mlcli = $svalue->Source->LocURI; //cli
				$mlsrv = $svalue->Target->LocURI; //srv
				lg("Map $lsrv -> $lcli; $mlsrv -> $mlcli");
				$map = map_response($i,$cmdid,$mesgid,$authenticated,$lcli,$lsrv,$mlcli,$mlsrv,$source_s);
				$send .= $map;
				$i++;
			}
			break;
		default:
			break;
	}
	
}
//*/

if (isset($syncbod->Final)) { #seems that this should be this way
	#documentation says that there should be Alert message with code 222, but if there any other commands to be sent - Alert may be omitted
	$send .= "<Final/></SyncBody></SyncML>";
} else {
	$send .= "</SyncBody></SyncML>";
};
//$sent = xml2wbxml($send);
if ($keep_exchange > 0) {
	fwrite($handler, "S-\>C\n");
	fwrite($handler, $send);
	fwrite($handler, "S-\>C\n");
	fwrite($hand_rs, $send);
};
//exec("xml2wbxml -o output2.xml progress_2.xml");
//echo $sent;
if ($input_type == 'wbxml') {
	$szb = strlen($send); //SiZe Before
	$send = wbxml_encode($send);
	$sza = strlen($send); //buggy on unicode
	lg("Compression " . (100*(1-$sza/$szb)) . "%. Before=$szb, after=$sza");
}
lg("---------------------------------------");
echo $send;

#$f=fopen("out_$mesgid.wbx", w);
#fwrite($f,$send);
#fclose($f);

if ($keep_exchange > 0) {
	fclose($handler);
	fclose($hand_rq);
	fclose($hand_rs);
};
?>
