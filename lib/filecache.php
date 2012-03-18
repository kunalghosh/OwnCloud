<?php

/**
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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
*/

/**
 * provide caching for filesystem info in the database
 *
 * not used by OC_Filesystem for reading filesystem info,
 * instread apps should use OC_FileCache::get where possible
 *
 * It will try to keep the data up to date but changes from outside ownCloud can invalidate the cache
 */
class OC_FileCache{
	private static $savedData=array();
	
	/**
	 * get the filesystem info from the cache
	 * @param string path
	 * @param string root (optional)
	 * @return array
	 *
	 * returns an assiciative array with the following keys:
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function get($path,$root=''){
		if(self::isUpdated($path,$root)){
			if(!$root){//filesystem hooks are only valid for the default root
				OC_Hook::emit('OC_Filesystem','post_write',array('path'=>$path));
			}else{
				self::fileSystemWatcherWrite(array('path'=>$path),$root);
			}
		}
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$path=$root.$path;
		$query=OC_DB::prepare('SELECT ctime,mtime,mimetype,size,encrypted,versioned,writable FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path))->fetchRow();
		if(is_array($result)){
			return $result;
		}else{
			OC_Log::write('get(): file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return false;
		}
	}

	/**
	 * put filesystem info in the cache
	 * @param string $path
	 * @param array data
	 * @param string root (optional)
	 *
	 * $data is an assiciative array in the same format as returned by get
	 */
	public static function put($path,$data,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$path=$root.$path;
		if($path=='/'){
			$parent=-1;
		}else{
			$parent=self::getFileId(dirname($path));
		}
		$id=self::getFileId($path);
		if($id!=-1){
			self::update($id,$data);
			return;
		}
		if(isset(self::$savedData[$path])){
			$data=array_merge($data,self::$savedData[$path]);
			unset(self::$savedData[$path]);
		}
		if(!isset($data['size']) or !isset($data['mtime'])){//save incomplete data for the next time we write it
			self::$savedData[$path]=$data;
			return;
		}
		if(!isset($data['encrypted'])){
			$data['encrypted']=false;
		}
		if(!isset($data['versioned'])){
			$data['versioned']=false;
		}
		$mimePart=dirname($data['mimetype']);
		$user=OC_User::getUser();
		$query=OC_DB::prepare('INSERT INTO *PREFIX*fscache(parent, name, path, size, mtime, ctime, mimetype, mimepart,user,writable,encrypted,versioned) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
		$result=$query->execute(array($parent,basename($path),$path,$data['size'],$data['mtime'],$data['ctime'],$data['mimetype'],$mimePart,$user,$data['writable'],$data['encrypted'],$data['versioned']));
		if(OC_DB::isError($result)){
			OC_Log::write('files','error while writing file('.$path.') to cache',OC_Log::ERROR);
		}
	}

	/**
	 * update filesystem info of a file
	 * @param int $id
	 * @param array $data
	 */
	private static function update($id,$data){
		$arguments=array();
		$queryParts=array();
		foreach(array('size','mtime','ctime','mimetype','encrypted','versioned','writable') as $attribute){
			if(isset($data[$attribute])){
				$arguments[]=$data[$attribute];
				$queryParts[]=$attribute.'=?';
			}
		}
		if(isset($data['mimetype'])){
			$arguments[]=dirname($data['mimetype']);
			$queryParts[]='mimepart=?';
		}
		$arguments[]=$id;
		
		$sql = 'UPDATE *PREFIX*fscache SET '.implode(' , ',$queryParts).' WHERE id=?';
		$query=OC_DB::prepare($sql);
		$result=$query->execute($arguments);
		if(OC_DB::isError($result)){
			OC_Log::write('files','error while updating file('.$path.') in cache',OC_Log::ERROR);
		}
	}

	/**
	 * register a file move in the cache
	 * @param string oldPath
	 * @param string newPath
	 * @param string root (optional)
	 */
	public static function move($oldPath,$newPath,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$oldPath=$root.$oldPath;
		$newPath=$root.$newPath;
		$newParent=self::getParentId($newPath);
		$query=OC_DB::prepare('UPDATE *PREFIX*fscache SET parent=? ,name=?, path=? WHERE path=?');
		$query->execute(array($newParent,basename($newPath),$newPath,$oldPath));
	}

	/**
	 * delete info from the cache
	 * @param string/int $file
	 * @param string root (optional)
	 */
	public static function delete($file,$root=''){
		if(!is_numeric($file)){
			if(!$root){
				$root=OC_Filesystem::getRoot();
			}
			if($root=='/'){
				$root='';
			}
			$path=$root.$file;
			self::delete(self::getFileId($path));
		}elseif($file!=-1){
			$query=OC_DB::prepare('SELECT id FROM *PREFIX*fscache WHERE parent=?');
			$result=$query->execute(array($file));
			while($child=$result->fetchRow()){
				self::delete(intval($child['id']));
			}
			$query=OC_DB::prepare('DELETE FROM *PREFIX*fscache WHERE id=?');
			$query->execute(array($file));
		}
	}
	
	/**
	 * return array of filenames matching the querty
	 * @param string $query
	 * @param boolean $returnData
	 * @param string root (optional)
	 * @return array of filepaths
	 */
	public static function search($search,$returnData=false,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$rootLen=strlen($root);
		if(!$returnData){
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE name LIKE ? AND user=?');
		}else{
			$query=OC_DB::prepare('SELECT * FROM *PREFIX*fscache WHERE name LIKE ? AND user=?');
		}
		$result=$query->execute(array("%$search%",OC_User::getUser()));
		$names=array();
		while($row=$result->fetchRow()){
			if(!$returnData){
				$names[]=substr($row['path'],$rootLen);
			}else{
				$row['path']=substr($row['path'],$rootLen);
				$names[]=$row;
			}
		}
		return $names;
	}

	/**
	 * get all files and folders in a folder
	 * @param string path
	 * @param string root (optional)
	 * @return array
	 *
	 * returns an array of assiciative arrays with the following keys:
	 * - name
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function getFolderContent($path,$root=''){
		if(self::isUpdated($path,$root)){
			self::updateFolder($path,$root);
		}
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$path=$root.$path;
		$parent=self::getFileId($path);
		$query=OC_DB::prepare('SELECT name,ctime,mtime,mimetype,size,encrypted,versioned,writable FROM *PREFIX*fscache WHERE parent=?');
		$result=$query->execute(array($parent))->fetchAll();
		if(is_array($result)){
			return $result;
		}else{
			OC_Log::write('getFolderContent(): file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return false;
		}
	}

	/**
	 * check if a file or folder is in the cache
	 * @param string $path
	 * @param string root (optional)
	 * @return bool
	 */
	public static function inCache($path,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$path=$root.$path;
		return self::getFileId($path)!=-1;
	}

	/**
	 * get the file id as used in the cache
	 * @param string $path
	 * @return int
	 */
	private static function getFileId($path){
		$query=OC_DB::prepare('SELECT id FROM *PREFIX*fscache WHERE path=?');
		if(OC_DB::isError($query)){
			OC_Log::write('files','error while getting file id of '.$path,OC_Log::ERROR);
			return -1;
		}
		$result=$query->execute(array($path));
		if(OC_DB::isError($result)){
			OC_Log::write('files','error while getting file id of '.$path,OC_Log::ERROR);
			return -1;
		}
		$result=$result->fetchRow();
		if(is_array($result)){
			return $result['id'];
		}else{
			OC_Log::write('getFileId(): file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return -1;
		}
	}

	/**
	 * get the file id of the parent folder, taking into account '/' has no parent
	 * @param string $path
	 * @return int
	 */
	private static function getParentId($path){
		if($path=='/'){
			return -1;
		}else{
			return self::getFileId(dirname($path));
		}
	}

	/**
	 * called when changes are made to files
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherWrite($params,$root=''){
		if(!$root){
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView(($root=='/')?'':$root);
		}
		
		$path=$params['path'];
		$fullPath=$view->getRoot().$path;
		$mimetype=$view->getMimeType($path);
		$dir=$view->is_dir($path.'/');
		//dont use self::get here, we don't want inifinte loops when a file has changed
		$cachedSize=self::getCachedSize($path,$root);
		$size=0;
		if($dir){
			if(self::inCache($path,$root)){
				$parent=self::getFileId($fullPath);
				$query=OC_DB::prepare('SELECT size FROM *PREFIX*fscache WHERE parent=?');
				$result=$query->execute(array($parent));
				while($row=$result->fetchRow()){
					$size+=$row['size'];
				}
				$mtime=$view->filemtime($path);
				$ctime=$view->filectime($path);
				$writable=$view->is_writable($path);
				self::put($path,array('size'=>$size,'mtime'=>$mtime,'ctime'=>$ctime,'mimetype'=>$mimetype,'writable'=>$writable));
			}else{
				$count=0;
				self::scan($path,null,$count,$root);
			}
		}else{
			$size=self::scanFile($path,$root);
		}
		self::increaseSize(dirname($fullPath),$size-$cachedSize);
	}
	
	public static function getCached($path,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}else{
			if($root=='/'){
				$root='';
			}
		}
		$path=$root.$path;
		$query=OC_DB::prepare('SELECT ctime,mtime,mimetype,size,encrypted,versioned,writable FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path))->fetchRow();
		if(is_array($result)){
			if(isset(self::$savedData[$path])){
				$result=array_merge($result,self::$savedData[$path]);
			}
			return $result;
		}else{
			OC_Log::write('get(): file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return false;
		}
	}
	
	private static function getCachedSize($path,$root){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}else{
			if($root=='/'){
				$root='';
			}
		}
		$path=$root.$path;
		$query=OC_DB::prepare('SELECT size FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path));
		if($row=$result->fetchRow()){
			return $row['size'];
		}else{//file not in cache
			return 0;
		}
	}

	/**
	 * called when files are deleted
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherDelete($params,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$path=$params['path'];
		$fullPath=$root.$path;
		if(self::getFileId($fullPath)==-1){
			return;
		}
		$size=self::getCachedSize($path,$root);
		self::increaseSize(dirname($fullPath),-$size);
		self::delete($path);
	}

	/**
	 * called when files are deleted
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherRename($params,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}
		if($root=='/'){
			$root='';
		}
		$oldPath=$params['oldpath'];
		$newPath=$params['newpath'];
		$fullOldPath=$root.$oldPath;
		$fullNewPath=$root.$newPath;
		if(($id=self::getFileId($fullOldPath))!=-1){
			$oldSize=self::getCachedSize($oldPath,$root);
		}else{
			return;
		}
		$size=OC_Filesystem::filesize($oldPath);
		self::increaseSize(dirname($fullOldPath),-$oldSize);
		self::increaseSize(dirname($fullNewPath),$oldSize);
		self::move($oldPath,$newPath);
	}

	/**
	 * adjust the size of the parent folders
	 * @param string $path
	 * @param int $sizeDiff
	 */
	private static function increaseSize($path,$sizeDiff){
		if($sizeDiff==0) return;
		while(($id=self::getFileId($path))!=-1){//walk up the filetree increasing the size of all parent folders
			$query=OC_DB::prepare('UPDATE *PREFIX*fscache SET size=size+? WHERE id=?');
			$query->execute(array($sizeDiff,$id));
			$path=dirname($path);
		}
	}

	/**
	 * recursively scan the filesystem and fill the cache
	 * @param string $path
	 * @param OC_EventSource $enventSource (optional)
	 * @param int count (optional)
	 * @param string root (optionak)
	 */
	public static function scan($path,$eventSource=false,&$count=0,$root=''){
		if(!$root){
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView(($root=='/')?'':$root);
		}
		self::scanFile($path,$root);
		$dh=$view->opendir($path.'/');
		$totalSize=0;
		if($dh){
			while (($filename = readdir($dh)) !== false) {
				if($filename != '.' and $filename != '..'){
					$file=$path.'/'.$filename;
					if($view->is_dir($file.'/')){
						if($eventSource){
							$eventSource->send('scanning',array('file'=>$file,'count'=>$count));
						}
						self::scan($file,$eventSource,$count,$root);
					}else{
						$totalSize+=self::scanFile($file,$root);
						$count++;
					}
				}
			}
		}
		self::increaseSize($view->getRoot().$path,$totalSize);
	}

	/**
	 * scan a single file
	 * @param string path
	 * @param string root (optional)
	 * @return int size of the scanned file
	 */
	public static function scanFile($path,$root=''){
		if(!$root){
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView(($root=='/')?'':$root);
		}
		if(!$view->is_readable($path)) return; //cant read, nothing we can do
		$stat=$view->stat($path);
		$mimetype=$view->getMimeType($path);
		$writable=$view->is_writable($path);
		$stat['mimetype']=$mimetype;
		$stat['writable']=$writable;
		if($path=='/'){
			$path='';
		}
		self::put($path,$stat,$root);
		return $stat['size'];
	}

	/**
	 * find files by mimetype
	 * @param string $part1
	 * @param string $part2 (optional)
	 * @param string root (optional)
	 * @return array of file paths
	 *
	 * $part1 and $part2 together form the complete mimetype.
	 * e.g. searchByMime('text','plain')
	 *
	 * seccond mimetype part can be ommited
	 * e.g. searchByMime('audio')
	 */
	public static function searchByMime($part1,$part2=null,$root=null){
		if(!$root){
			$root=OC_Filesystem::getRoot();
		}elseif($root=='/'){
			$root='';
		}
		$rootLen=strlen($root);
		$root .= '%';
		$user=OC_User::getUser();
		if(!$part2){
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE mimepart=? AND user=? AND path LIKE ?');
			$result=$query->execute(array($part1,$user, $root));
		}else{
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE mimetype=? AND user=? AND path LIKE ? ');
			$result=$query->execute(array($part1.'/'.$part2,$user, $root));
		}
		$names=array();
		while($row=$result->fetchRow()){
			$names[]=substr($row['path'],$rootLen);
		}
		return $names;
	}

	/**
	 * check if a file or folder is updated outside owncloud
	 * @param string path
	 * @param string root (optional)
	 * @return bool
	 */
	public static function isUpdated($path,$root=''){
		if(!$root){
			$root=OC_Filesystem::getRoot();
			$view=OC_Filesystem::getView();
		}else{
			if($root=='/'){
				$root='';
			}
			$view=new OC_FilesystemView($root);
		}
		if(!$view->file_exists($path)){
			return false;
		}
		$mtime=$view->filemtime($path);
		$isDir=$view->is_dir($path);
		$path=$root.$path;
		$query=OC_DB::prepare('SELECT mtime FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path));
		if($row=$result->fetchRow()){
			$cachedMTime=$row['mtime'];
			return ($mtime>$cachedMTime);
		}else{//file not in cache, so it has to be updated
			return !($isDir);//new folders are handeled sperate
		}
	}

	/**
	 * update the cache according to changes in the folder
	 * @param string path
	 * @param string root (optional)
	 */
	private static function updateFolder($path,$root=''){
		if(!$root){
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView(($root=='/')?'':$root);
		}
		$dh=$view->opendir($path);
		if($dh){//check for changed/new files
			while (($filename = readdir($dh)) !== false) {
				if($filename != '.' and $filename != '..'){
					$file=$path.'/'.$filename;
					if(self::isUpdated($file,$root)){
						if(!$root){//filesystem hooks are only valid for the default root
							OC_Hook::emit('OC_Filesystem','post_write',array('path'=>$file));
						}else{
							self::fileSystemWatcherWrite(array('path'=>$file),$root);
						}
					}
				}
			}
		}

		//check for removed files, not using getFolderContent to prevent loops
		$parent=self::getFileId($view->getRoot().$path);
		$query=OC_DB::prepare('SELECT name FROM *PREFIX*fscache WHERE parent=?');
		$result=$query->execute(array($parent));
		while($row=$result->fetchRow()){
			$file=$path.'/'.$row['name'];
			if(!$view->file_exists($file)){
				if(!$root){//filesystem hooks are only valid for the default root
					OC_Hook::emit('OC_Filesystem','post_delete',array('path'=>$file));
				}else{
					self::fileSystemWatcherDelete(array('path'=>$file),$root);
				}
			}
		}
		//update the folder last, so we can calculate the size correctly
		if(!$root){//filesystem hooks are only valid for the default root
			OC_Hook::emit('OC_Filesystem','post_write',array('path'=>$path));
		}else{
			self::fileSystemWatcherWrite(array('path'=>$path),$root);
		}
	}
}

//watch for changes and try to keep the cache up to date
OC_Hook::connect('OC_Filesystem','post_write','OC_FileCache','fileSystemWatcherWrite');
OC_Hook::connect('OC_Filesystem','post_delete','OC_FileCache','fileSystemWatcherDelete');
OC_Hook::connect('OC_Filesystem','post_rename','OC_FileCache','fileSystemWatcherRename');
