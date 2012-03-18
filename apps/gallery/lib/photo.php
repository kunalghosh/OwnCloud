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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Lesser General Public 
* License along with this library. If not, see <http://www.gnu.org/licenses/>.
* 
*/

class OC_Gallery_Photo {
	public static function create($albumId, $img){
		$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_photos (album_id, file_path) VALUES (?, ?)');
		$stmt->execute(array($albumId, $img));
	}
	public static function find($albumId, $img=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_photos WHERE album_id = ?';
		$args = array($albumId);
		if (!is_null($img)){
			$sql .= ' AND file_path = ?';
			$args[] = $img;
		}
		$stmt = OC_DB::prepare($sql);
		return $stmt->execute($args);
	}
	public static function findForAlbum($owner, $album_name){
		$stmt = OC_DB::prepare('SELECT *'
			.' FROM *PREFIX*gallery_photos photos,'
				.' *PREFIX*gallery_albums albums'
			.' WHERE albums.uid_owner = ?'
				.' AND albums.album_name = ?'
				.' AND photos.album_id = albums.album_id');
		return $stmt->execute(array($owner, $album_name));
	}

	public static function removeByPath($path) {
		$stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos WHERE file_path LIKE ?');
		$stmt->execute(array($path));
	}

	public static function removeById($id) {
		$stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos WHERE photo_id = ?');
		$stmt->execute(array($id));
	}

	public static function removeByAlbumId($albumid) {
		$stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos WHERE album_id = ?');
		$stmt->execute(array($albumid));
	}

	public static function changePath($oldAlbumId, $newAlbumId, $oldpath, $newpath) {
		$stmt = OC_DB::prepare("UPDATE *PREFIX*gallery_photos SET file_path = ?, album_id = ? WHERE album_id = ? and file_path = ?");
		$stmt->execute(array($newpath, $newAlbumId, $oldAlbumId, $oldpath));
	}

	public static function getThumbnail($image_name) {
		$save_dir = OC_Config::getValue("datadirectory").'/'. OC_User::getUser() .'/gallery/';
		$save_dir .= dirname($image_name). '/';
		$image_path = $image_name;
		$thumb_file = $save_dir . basename($image_name);
		if (file_exists($thumb_file)) {
			$image = new OC_Image($thumb_file);
		} else {
			$image_path = OC_Filesystem::getLocalFile($image_path);
			if(!file_exists($image_path)) {
				return null;
			}
			$image = new OC_Image($image_path);
			if ($image->valid()) {
				$image->centerCrop();
				$image->resize(200);
				$image->fixOrientation();
				if (!is_dir($save_dir)) {
					mkdir($save_dir, 0777, true);
				}
				$image->save($thumb_file);
			}
		}
		if ($image->valid()) {
			return $image;
		}
		return null;
	}

	public static function getGalleryRoot() {
		return OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'root', '');
	}
}
