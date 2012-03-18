<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bart.p.pl@gmail.com
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

header('Content-type: text/html; charset=UTF-8') ;
require_once('../../../lib/base.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

function handleRename($oldname, $newname) {
  OC_Gallery_Album::rename($oldname, $newname, OC_User::getUser());
  OC_Gallery_Album::changeThumbnailPath($oldname, $newname);
}

function handleRemove($name) {
  $album_id = OC_Gallery_Album::find(OC_User::getUser(), $name);
  $album_id = $album_id->fetchRow();
  $album_id = $album_id['album_id'];
  OC_Gallery_Album::remove(OC_User::getUser(), $name);
  OC_Gallery_Photo::removeByAlbumId($album_id);
}

function handleGetThumbnails($albumname) {
  OC_Response::enableCaching(3600 * 24); // 24 hour
  $thumbnail = OC::$CONFIG_DATADIRECTORY.'/../gallery/'.urldecode($albumname).'.png';
  header('Content-Type: '.OC_Image::getMimeTypeForFile($thumbnail));
  OC_Response::sendFile($thumbnail);
}

function handleGalleryScanning() {
  OC_DB::beginTransaction();
  set_time_limit(0);
  OC_Gallery_Album::cleanup();
  $eventSource = new OC_EventSource();
  OC_Gallery_Scanner::scan($eventSource);
  $eventSource->close();
  OC_DB::commit();
}

function handleFilescan($cleanup) {
  if ($cleanup) OC_Gallery_Album::cleanup();
  $pathlist = OC_Gallery_Scanner::find_paths();
  sort($pathlist);
  OC_JSON::success(array('paths' => $pathlist));
}

function handleStoreSettings($root, $order) {
  if (!OC_Filesystem::file_exists($root)) {
    OC_JSON::error(array('cause' => 'No such file or directory'));
    return;
  }
  if (!OC_Filesystem::is_dir($root)) {
    OC_JSON::error(array('cause' => $root . ' is not a directory'));
    return;
  }

  $current_root = OC_Preferences::getValue(OC_User::getUser(),'gallery', 'root', '/');
  $root = trim($root);
  $root = rtrim($root, '/').'/';
  $rescan = $current_root==$root?'no':'yes';
  OC_Preferences::setValue(OC_User::getUser(), 'gallery', 'root', $root);
  OC_Preferences::setValue(OC_User::getUser(), 'gallery', 'order', $order);
  OC_JSON::success(array('rescan' => $rescan));
}

function handleGetGallery($path) {
  $a = array();
  $root = OC_Preferences::getValue(OC_User::getUser(),'gallery', 'root', '/');
  $path = utf8_decode(rtrim($root.$path,'/'));
  if($path == '') $path = '/';
  $pathLen = strlen($path);
  $result = OC_Gallery_Album::find(OC_User::getUser(), null, $path);
  $album_details = $result->fetchRow();

  $result = OC_Gallery_Album::find(OC_User::getUser(), null, null, $path);

  while ($r = $result->fetchRow()) {
    $album_name = $r['album_name'];
    $size=OC_Gallery_Album::getAlbumSize($r['album_id']);
    // this is a fallback mechanism and seems expensive
    if ($size == 0) $size = OC_Gallery_Album::getIntermediateGallerySize($r['album_path']);

    $a[] = array('name' => utf8_encode($album_name), 'numOfItems' => min($size, 10),'path'=>substr($r['album_path'], $pathLen));
  }
  
  $result = OC_Gallery_Photo::find($album_details['album_id']);

  $p = array();

  while ($r = $result->fetchRow()) {
    $p[] = utf8_encode($r['file_path']);
  }

  OC_JSON::success(array('albums'=>$a, 'photos'=>$p));
}

if ($_GET['operation']) {
  switch($_GET['operation']) {
  case 'rename':
	  handleRename($_GET['oldname'], $_GET['newname']);
	  OC_JSON::success(array('newname' => $_GET['newname']));
	break;
  case 'remove':
	  handleRemove($_GET['name']);
	  OC_JSON::success();
    break;
  case 'get_covers':
    handleGetThumbnails(urldecode($_GET['albumname']));
    break;
  case 'scan':
    handleGalleryScanning();
    break;
  case 'store_settings':
    handleStoreSettings($_GET['root'], $_GET['order']);
    break;
  case 'get_gallery':
    handleGetGallery($_GET['path']);
    break;
  default:
    OC_JSON::error(array('cause' => 'Unknown operation'));
  }
}
?>
