<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 * TODO: Translatable strings.
 *       Remember to delete tmp file at some point.
 */
// Init owncloud
require_once('../../../lib/base.php');
OC_Log::write('contacts','ajax/savecrop.php: Huzzah!!!', OC_Log::DEBUG);

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

// foreach ($_POST as $key=>$element) {
// 	OC_Log::write('contacts','ajax/savecrop.php: '.$key.'=>'.$element, OC_Log::DEBUG);
// }

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OC_JSON::setContentTypeHeader('text/plain');

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/savecrop.php: '.$msg, OC_Log::DEBUG);
	exit();
}

$image = null;

$x1 = (isset($_POST['x1']) && $_POST['x1']) ? $_POST['x1'] : -1;
//$x2 = isset($_POST['x2']) ? $_POST['x2'] : -1;
$y1 = (isset($_POST['y1']) && $_POST['y1']) ? $_POST['y1'] : -1;
//$y2 = isset($_POST['y2']) ? $_POST['y2'] : -1;
$w = (isset($_POST['w']) && $_POST['w']) ? $_POST['w'] : -1;
$h = (isset($_POST['h']) && $_POST['h']) ? $_POST['h'] : -1;
$tmp_path = isset($_POST['tmp_path']) ? $_POST['tmp_path'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : '';

if(in_array(-1, array($x1, $y1, $w, $h))) {
	bailOut('Wrong crop dimensions: '.implode(', ', array($x1, $y1, $w, $h)));
}

if($tmp_path == '') {
	bailOut('Missing path to temporary file.');
}

if($id == '') {
	bailOut('Missing contact id.');
}

OC_Log::write('contacts','savecrop.php: files: '.$tmp_path.'  exists: '.file_exists($tmp_path), OC_Log::DEBUG);

if(file_exists($tmp_path)) {
	$image = new OC_Image();
	if($image->loadFromFile($tmp_path)) {
		if($image->crop($x1, $y1, $w, $h)) {
			if($image->resize(200)) {
				$tmpfname = tempnam("/tmp", "occCropped"); // create a new file because of caching issues.
				if($image->save($tmpfname)) {
					unlink($tmp_path);
					$card = OC_Contacts_App::getContactVCard($id);
					if(!$card) {
						unlink($tmpfname);
						bailOut('Error getting contact object.');
					}
					if($card->__isset('PHOTO')) {
						OC_Log::write('contacts','savecrop.php: files: PHOTO property exists.', OC_Log::DEBUG);
						$property = $card->__get('PHOTO');
						if(!$property) {
							unlink($tmpfname);
							bailOut('Error getting PHOTO property.');
						}
						$property->setValue($image->__toString());
						$property->parameters[] = new Sabre_VObject_Parameter('ENCODING', 'b');
						$property->parameters[] = new Sabre_VObject_Parameter('TYPE', $image->mimeType());
						$card->__set('PHOTO', $property);
					} else {
						OC_Log::write('contacts','savecrop.php: files: Adding PHOTO property.', OC_Log::DEBUG);
						$card->addProperty('PHOTO', $image->__toString(), array('ENCODING' => 'b', 'TYPE' => $image->mimeType()));
					}
					if(!OC_Contacts_VCard::edit($id,$card->serialize())) {
						bailOut('Error saving contact.');
					}
					unlink($tmpfname);
					//$result=array( "status" => "success", 'mime'=>$image->mimeType(), 'tmp'=>$tmp_path);
					$tmpl = new OC_TEMPLATE("contacts", "part.contactphoto");
					$tmpl->assign('tmp_path', $tmpfname);
					$tmpl->assign('mime', $image->mimeType());
					$tmpl->assign('id', $id);
					$tmpl->assign('width', $image->width());
					$tmpl->assign('height', $image->height());
					$page = $tmpl->fetchPage();
					OC_JSON::success(array('data' => array('page'=>$page, 'tmp'=>$tmpfname)));
					exit();
				} else {
					if(file_exists($tmpfname)) {
						unlink($tmpfname);
					}
					bailOut('Error saving temporary image');
				}
			} else {
				bailOut('Error resizing image');
			}
		} else {
			bailOut('Error cropping image');
		}
	} else {
		bailOut('Error creating temporary image');
	}
} else {
	bailOut('Error finding image: '.$tmp_path);
}

if($tmp_path != '' && file_exists($tmp_path)) {
	unlink($tmp_path);
}

?>
