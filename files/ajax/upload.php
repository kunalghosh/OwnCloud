<?php

// Init owncloud
require_once('../../lib/base.php');

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OC_JSON::setContentTypeHeader('text/plain');

OC_JSON::checkLoggedIn();

if (!isset($_FILES['files'])) {
	OC_JSON::error(array("data" => array( "message" => "No file was uploaded. Unknown error" )));
	exit();
}
foreach ($_FILES['files']['error'] as $error) {
	if ($error != 0) {
		$l=new OC_L10N('files');
		$errors = array(
			UPLOAD_ERR_OK=>$l->t("There is no error, the file uploaded with success"),
			UPLOAD_ERR_INI_SIZE=>$l->t("The uploaded file exceeds the upload_max_filesize directive in php.ini").ini_get('upload_max_filesize'),
			UPLOAD_ERR_FORM_SIZE=>$l->t("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"),
			UPLOAD_ERR_PARTIAL=>$l->t("The uploaded file was only partially uploaded"),
			UPLOAD_ERR_NO_FILE=>$l->t("No file was uploaded"),
			UPLOAD_ERR_NO_TMP_DIR=>$l->t("Missing a temporary folder"),
			UPLOAD_ERR_CANT_WRITE=>$l->t('Failed to write to disk'),
		);
		OC_JSON::error(array("data" => array( "message" => $errors[$error] )));
		exit();
	}
}
$files=$_FILES['files'];

$dir = $_POST['dir'];
$dir .= '/';
$error='';

$totalSize=0;
foreach($files['size'] as $size){
	$totalSize+=$size;
}
if($totalSize>OC_Filesystem::free_space('/')){
	OC_JSON::error(array("data" => array( "message" => "Not enough space available" )));
	exit();
}

$result=array();
if(strpos($dir,'..') === false){
	$fileCount=count($files['name']);
	for($i=0;$i<$fileCount;$i++){
		$target=stripslashes($dir) . $files['name'][$i];
		if(is_uploaded_file($files['tmp_name'][$i]) and OC_Filesystem::fromTmpFile($files['tmp_name'][$i],$target)){
			$meta=OC_FileCache::getCached($target);
			$result[]=array( "status" => "success", 'mime'=>$meta['mimetype'],'size'=>$meta['size'],'name'=>$files['name'][$i]);
		}
	}
	OC_JSON::encodedPrint($result);
	exit();
}else{
	$error='invalid dir';
}

OC_JSON::error(array('data' => array('error' => $error, "file" => $fileName)));

?>
