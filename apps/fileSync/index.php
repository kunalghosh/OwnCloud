<?php

//ini_set('display_errors','Off');
error_reporting(0);
$debug = True;
$appName = "fileSync";
require_once('../../lib/base.php');
//first get the user credentials
$user = $_SERVER["PHP_AUTH_USER"];
$pass = $_SERVER["PHP_AUTH_PW"];
//Now authenticate the user
if(!OC_User::checkPassword($user,$pass)){
    //Failed authentication no point proceeding.
    OC_Log::write($appName, "Failed Login with Username : ".$user." Password : ".$pass, OC_Log::ERROR);
    exit();
}

//include("logging.php");
include("sync_send.php");
include("conflicts.php");
include("states.php");
include("files_fileSync.php");
include("get_response.php");
//include("config_fileSync.php");

//Now that the user is authenticated.
//We'll initialize his File Store.
OC_Util::setupFS($user);
//And Other Global Variables
$base_dir= OC_Filesystem::getMountPoint(OC_Filesystem::getRoot());//VERY VERY Impt without this the files will not be created at the right folder.
$user_dir=$base_dir;
echo "Base Dir $base_dir\n";
//exit();
$do_log = 1;//Do you want logging ?
if($debug == True){
    //If debug is True
    $keep_exchange = 1;//keep the exchange files in separate in.xml , out.xml and progress.xml
}
else{
    $keep_exchange = 0;//don't store exchanges in files in.xml , out.xml and progress.xml
}
    
$dbuser = "";//unused
$dbpass = "";//unused
$dbtype = "";//unused
/* Possible values of conflict resolution options:
 * server   : written as "ser" : Server wins
 * client   : written as "cli" : Client wins
 * duplicate: written as "dup" : Old file copied to a new file with timestamp and a new file is created.
 * delete   : written as "del" : Delete existing file with the same name.
 * merge    : written as "mer" : Merge teh conflicting files.
 */

//always merge conflict resolution strings are lower case
$SER = "ser";
$CLI = "cli";
$DUP = "dup";
$DEL = "del";
$MER = "mer";

$conflict_action1 = $MER;
$conflict_action2 = $DUP;
$unrestricted = 1;//1 -> allow user creation 0 -> disallow user creation

//parse_config(); #config_fileSync.php
//Lets open our log file for debugging purposes
if ($keep_exchange > 0){
	$hand_rq = OC_Filesystem::fopen("/in.xml", "w");
	$hand_rs = OC_Filesystem::fopen("/out.xml", "w");
        $handler = OC_Filesystem::fopen("/progress.xml", "a+");
        OC_Hook::emit("OC_Filesystem","signal_post_create");
};
//var_dump(OC_Filesystem::getInternalPath("/".$user));
//exit();
// assign POST data to variable (comment out for debugging)

$HTTP_RAW_POST_DATA = file_get_contents('php://input');

if($debug == True){
    echo "File get Contents ";
    var_dump($HTTP_RAW_POST_DATA);
}

$post_data = $HTTP_RAW_POST_DATA;
$ct = $_SERVER["CONTENT_TYPE"];

if ($ct == "application/vnd.syncml+wbxml") {
    $input_type = "wbxml"; 
    
}
else if ($ct == "application/vnd.syncml+xml") {
    $input_type = "xml"; 
    
}
else {
		$input_type = "unknown"; //pretend that it is xml
                //echo "<html>I only speak SyncML.</html>";
                //echo OC::$CONFIG_DATADIRECTORY;
                //echo " ROOT !!------------------------";
                //echo OC_Files::getDirectoryContent(OC::$CONFIG_DATADIRECTORY);
                //var_dump($_SERVER);
                //var_dump($data);
                if ($debug == False){
                    exit();
                }
                else{
                    $message = "CONTENT_TYPE Variable is empty ! :(, searched in _SERVER\n";
                    echo $message;
                    OC_Log::write($appName, $message, OC_Log::DEBUG);
                }
};
OC_Log::write($appName,"Recognized type: $input_type",OC_Log::DEBUG);

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
        OC_Hook::emit("OC_Filesystem", "post_write");
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
	if ($do_log > 0)    OC_Filesystem::unlink("log.txt");
	OC_Log::write(  $appName,"Log cleared due to 1st message", OC_Log::DEBUG);
};

OC_Log::write(  $appName,"Session: $sessid Message: $mesgid", OC_Log::DEBUG);
OC_Log::write(  $appName,"Source: $source", OC_Log::DEBUG);
OC_Log::write(  $appName,"Target: $target", OC_Log::DEBUG);
$auth = base64_decode($auth64);
$A = explode(':', $auth, 2);
//echo "PostData $_SERVER[SERVER_NAME]";
//$user = $A[0];
//$pass = $A[1];
//unset($A);
$user = $_SERVER["PHP_AUTH_USER"];
$pass = $_SERVER["PHP_AUTH_PW"];

OC_Log::write(  $appName,"user: $user pass: $pass", OC_Log::DEBUG);
if(OC_User::checkPassword($user,$pass)){
    OC_Log::write(  $appName,"core","Correct Login!",  OC_Log::DEBUG);
    OC_Log::write(  $appName,"Correct Login !", OC_Log::DEBUG);
    /*
     * $sharedFolder = "/".$user."/files";
     * $items = OC_Share::getItemsInFolder($sharedFolder);
     * echo " ITEMS--->";
     * var_dump($items);    
     * $sharedFolder = "/".$user;    
     * $items = OC_Share::getItemsInFolder(OC_Filesystem::getInternalPath($sharedFolder));    
     * echo " ITEMS2--->";
     * var_dump($items);
     * 
     */
    $query = OC_DB::prepare("SELECT * FROM *PREFIX*fscache  WHERE user = ?");
    $response = $query->execute(array($user))->fetchAll();
    //var_dump($response);
    echo "\nFS Internals-->\n"; 
    //var_dump(OC_Filesystem::getInternalPath("/".$user));
    //echo $response[3]["path"];
    //var_dump($response);
    $path="";
    foreach($response as $file){
        if($file["name"]=="Useful1.txt"){
            $path = $file["path"];
            break;
        }
    }
    //echo $path;
    //$filesystem = new OC_Filesystem("/".$user);
    //var_dump($filesystem->file_get_contents($response[3]["path"]));
    //echo " ";
    //OC_Util::setupFS($user);
    echo "Internal Path ";
    var_dump(OC_Filesystem::getRoot());
    //var_dump(OC_Files::getdirectorycontent(OC_Filesystem::getInternalPath("/".$user)));
    //var_dump(OC_Files::get(OC_Filesystem::getInternalPath("/"),"Useful1.txt"));//works
    //$internal_path = OC_Filesystem::getInternalPath($path);
    //echo $internal_path;
    //OC_Filesystem::file_put_contents($internal_path, "Hello How are You!");//doesn't work
    
    
    $z = OC_Filesystem::fopen("/Useful1.txt", "a");
    fwrite($z,"Another String of Text");
    fclose($z);
    
    
}
else{
    OC_Log::write(  $appName,"Incorrect Login!", OC_Log::DEBUG);
}
/////////////////////////////////////////////////parsing user profile
if (!empty($user)) {// if we have a username
	//$user = "anonymous";
	##search_session($sessid) -> $user
	$sess_f = OC_Filesystem::fopen("/sessions", "w");
	$sess_p = fread($sess_f, OC_Filesystem::filesize("/sessions"));
	OC_Log::write(  $appName,"'$sess_p'", OC_Log::DEBUG);
	fclose($sess_f);
	$sess_A = explode("\n", $sess_p);
	foreach ($sess_A as $value) {
		list($par_name, $par_value) = explode("=", $value, 2);
		$par_name = trim($par_name);
		$par_value = trim($par_value);
		OC_Log::write(  $appName,"$sessid == $par_value > $user = $par_name", OC_Log::DEBUG);
		if ($sessid == $par_value) $user = $par_name;
		#$SESS[strtolower(trim($par_value))] = trim($par_name);
	};
	#$user = $SESS[$sessid];
	##/search_session()
	/*if (! empty($user)) */OC_Log::write(  $appName,"for session $sessid found user $user.", OC_Log::DEBUG);
	#unset($sess_f,$sess_p,$sess_A,$par_name,$par_value,$SESS);
	unset($sess_f,$sess_p,$sess_A,$par_name,$par_value);
}
else if (empty($user)) {
    $user = "anonymous";
}
//$user_dir = $base_dir . '/' . $user;
//if ((!OC_Filesystem::is_dir($user_dir)) && ($unrestricted > 0)) { //DONE:and user creation is allowed
//$user_dir == "/" and is always there , we should check if /profile 
// file exists or not
if ((!OC_Filesystem::file_exists("/profile")) && ($unrestricted > 0)) {

	##mk_user($user_dir, $pass)
	//OC_Filesystem::mkdir($user_dir);
	OC_Log::write($appName, "dir created: $user_dir", OC_Log::DEBUG);
	$user_f = OC_Filesystem::fopen("/profile", "w");
	fwrite($user_f, "password=$pass\r\n");
	fclose($user_f);
	##/mk_user($user_dir, $pass)
}
else{
	//OC_Log::write( $appName,"index.php - dir not created already exists - user_dir = $user_dir", OC_Log::DEBUG);
    OC_Log::write( $appName,"profile - file not created already exists - user_dir = $user_dir", OC_Log::DEBUG);
};
if (! ($unrestricted > 0)) OC_Log::write( $appName,"user $user tried to login to restricted server", OC_Log::DEBUG);
OC_Log::write( $appName,"creating USER array", OC_Log::DEBUG);
#$USER = array();
#$USER["password"] = "invalid"; //for empty user to unauthenticate

# replace with file_load from get_responce.php
OC_Log::write( $appName,"user profile: " . $user_dir . '/' . "profile", OC_Log::DEBUG);
file_load("/profile", $user);

function user_profile_save()
{
        global $appName;
	global $USER;
	global $user_dir;
	$user_f = OC_Filesystem::fopen("/profile", "w");
	foreach ($USER as $key => $val) {
		fwrite($user_f, "$key=$val\r\n");
		OC_Log::write( $appName,"$key=$val", OC_Log::DEBUG);
	};
	fclose($user_f);
	OC_Log::write( $appName,"user_profile_save", OC_Log::DEBUG);
};

function user_profile_add($key, $value)
{
        global $appName;
	global $USER;
	global $user_dir;
	if (array_key_exists($key, $USER))
	{
		OC_Log::write( $appName,"key exists", OC_Log::DEBUG);
		$USER[strtolower(trim($key))] = trim($value);
		OC_Log::write( $appName,"USER[" . strtolower(trim($key)) . "] = trim($value)", OC_Log::DEBUG);
		OC_Log::write( $appName,$USER[strtolower(trim($key))] . "=" . trim($value), OC_Log::DEBUG);
		user_profile_save();		//DONE:writeit
	} else {
		OC_Log::write( $appName,"key not exists", OC_Log::DEBUG);
		$user_f = OC_Filesystem::fopen("/profile", "a");
		OC_Log::write( $appName,"u: $user_dir; $key=$value.", OC_Log::DEBUG);
		fwrite($user_f, "$key=$value\r\n");
		fclose($user_f);
		$USER[strtolower(trim($key))] = trim($value);
	};
};
function user_profile_get($key) {
        global $appName;
	global $USER;
	unset($value);
	OC_Log::write( $appName,">>>>$key>>>>" . $USER[$key], OC_Log::DEBUG);
	if (array_key_exists($key, $USER)) $value = $USER[$key];
	return $value;
}

/////////////////////////////////////////////////////////////////////
OC_Log::write( $appName,"user profile parsed", OC_Log::DEBUG);
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
	OC_Log::write( $appName,"authenticated: $authenticated", OC_Log::DEBUG);
} else {
	OC_Log::write( $appName,"unauhenticated $authenticated", OC_Log::DEBUG);
};
//now user is authenticated
if ($authenticated) {
	//Transaction start should be started here
	//TODO: make a copy of userdir till the end of transaction (and lock it)
	OC_Log::write( $appName,">>>>" . $USER["session"], OC_Log::DEBUG);
	user_profile_add("session", $sessid);
	OC_Log::write( $appName,">>>>" . $USER["session"], OC_Log::DEBUG);
	if ( (! isset($passkey)) OR ($passkey == "")) {
		$passkey = $_SERVER["UNIQUE_ID"];
		//as a source of random data. 24chars
		user_profile_add("passkey", $passkey);
	}
	$SESSIONS = Array();
	file_load("/sessions", $SESSIONS);
	$SESSIONS[$user] = "$sessid";
	file_save("/sessions", $SESSIONS);
	#this will keep sessions file clean, but makes a race conditions situation. Sessions can be lost, when something happens between load and save.
	#TODO: make sessions per-user. Maybe parse all users?..

};

$i = 1;
//header("Content-Type: application/vnd.syncml+xml");
//#header("Accept-Charset: UTF-8");

$vera = "1"; //protocol version
$verb = "1";
$ver = "$vera.$verb";
OC_Log::write( $appName,"acting like ver $ver", OC_Log::DEBUG);
if (array_key_exists("HTTPS", $_SERVER)) {
	$RespURI = $_SERVER["SCRIPT_URI"];
} else {
	$RespURI = "http://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["SCRIPT_NAME"];
};
$header = "<?xml version='1.0' encoding=\"UTF-8\"?>
<!DOCTYPE SyncML PUBLIC \"-//SYNCML//DTD SyncML $ver//EN\" \"http://www.openmobilealliance.org/tech/DTD/OMA-TS-SyncML_RepPro_DTD-V".$vera."_".$verb.".dtd\"><SyncML xmlns='SYNCML:SYNCML$ver'><SyncHdr><VerDTD>$ver</VerDTD><VerProto>SyncML/$ver</VerProto><SessionID>".$sessid."</SessionID><MsgID>".$mesgid."</MsgID><Target><LocURI>".$source."</LocURI></Target><Source><LocURI>".$target."</LocURI></Source><RespURI>" . $RespURI . "?passkey=$passkey</RespURI></SyncHdr><SyncBody>\n";
OC_Log::write( $appName,"header ready. passkey=$passkey", OC_Log::DEBUG);

$send = $header;
///*
#if ($synchdr->Cred->Data != "") {
$content = "<Status><CmdID>$i</CmdID><MsgRef>$mesgid</MsgRef><CmdRef>0</CmdRef><Cmd>SyncHdr</Cmd><TargetRef>$target</TargetRef><SourceRef>$source</SourceRef>";
if ($authenticated) {
	$content .= "<Data>212</Data></Status>";
	OC_Log::write( $appName,"Status for SyncHdr is 212 (authenticated)", OC_Log::DEBUG);
} else {
	$content .= "<Chal><Meta><Format xmlns=\"syncml:metinf\">b64</Format><Type xmlns=\"syncml:metinf\">syncml:auth-basic</Type></Meta></Chal><Data>407</Data></Status>";
	OC_Log::write( $appName,"Status for SyncHdr is 407 (unauthenticated)", OC_Log::DEBUG);
};
	$send .= $content;
	$i++;

OC_Log::write( $appName,"Parsing body", OC_Log::DEBUG);
#if (isset($syncbod->Final)) OC_Log::write( $appName,"Final present", OC_Log::DEBUG);
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
	OC_Log::write( $appName,"Found $key: Cli=$lcli Srv=$lsrv Cmd=$cmdid Data=$data CmdRef=$cmd", OC_Log::DEBUG);
	switch ($key) {
		case "Put":
			#$send .= $put_response;
			$data = $value->Item->Data; #override
			$datat = $data->asXML();
			$datas = substr($datat, 6, -7); #remove '<Data>'..
			OC_Log::write( $appName,"datas: $datas", OC_Log::DEBUG);
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
			OC_Log::write( $appName,"Alert Last=$last; Next=$next; SourceX=$source_s", OC_Log::DEBUG);
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
			if (isset($value->NumberOfChanges)) OC_Log::write( $appName,"NumberOfChanges: " . $value->NumberOfChanges, OC_Log::DEBUG);
			foreach($value->children() as $skey => $svalue) {
				$scmdid = $svalue->CmdID;
				OC_Log::write( $appName,"Sync: $skey; src: $slcli", OC_Log::DEBUG);
				OC_Log::write( $appName,$svalue->children(), OC_Log::DEBUG);
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
							OC_Log::write( $appName,"NEW SLSRV $slsrv", OC_Log::DEBUG);
							OC_Log::write( $appName,"Both Must have values slcli = $slcli slsrv = $slsrv ", OC_Log::DEBUG);#if slsrv is empty then the server doesnt know where to save the file :)
							$sdata = $ssvalue->Data;
							$MoreData = 0;
							OC_Log::write( $appName,"$key -> $skey -> Item[$slcli]", OC_Log::DEBUG);
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
				OC_Log::write( $appName,"Final present. Doing Sync", OC_Log::DEBUG);
				$sync = do_sync($i,$mesgid,$authenticated,$lcli,$lsrv,$source_s);
				$send .= $sync;
			} else {
				OC_Log::write( $appName,"No Final. Not last part. Sync delayed", OC_Log::DEBUG);
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
				OC_Log::write( $appName,"Map $lsrv -> $lcli; $mlsrv -> $mlcli", OC_Log::DEBUG);
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
	OC_Log::write( $appName,"Compression " . (100*(1-$sza/$szb)) . "%. Before=$szb, after=$sza", OC_Log::DEBUG);
}
OC_Log::write( $appName,"---------------------------------------", OC_Log::DEBUG);
echo $send;

#$f=OC_Filesystem::fopen("out_$mesgid.wbx", w);
#fwrite($f,$send);
#fclose($f);

if ($keep_exchange > 0) {
	fclose($handler);
	fclose($hand_rq);
	fclose($hand_rs);
};
?>
