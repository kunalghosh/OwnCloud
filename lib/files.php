<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * Class for fileserver access
 *
 */
class OC_Files {
	static $tmpFiles=array();

	/**
	* get the content of a directory
	* @param dir $directory
	*/
	public static function getDirectoryContent($directory){
		global $CONFIG_DATADIRECTORY;
		if(strpos($directory,$CONFIG_DATADIRECTORY)===0){
			$directory=substr($directory,strlen($CONFIG_DATADIRECTORY));
		}
		$filesfound=true;
		$content=array();
		$dirs=array();
		$file=array();
		$files=array();
		if(OC_Filesystem::is_dir($directory)) {
			if ($dh = OC_Filesystem::opendir($directory)) {
			while (($filename = readdir($dh)) !== false) {
				if($filename<>'.' and $filename<>'..' and substr($filename,0,1)!='.'){
					$file=array();
					$filesfound=true;
					$file['name']=$filename;
					$file['directory']=$directory;
					$stat=OC_Filesystem::stat($directory.'/'.$filename);
					$file=array_merge($file,$stat);
					$file['size']=OC_Filesystem::filesize($directory.'/'.$filename);
					$file['mime']=OC_Files::getMimeType($directory .'/'. $filename);
					$file['readable']=OC_Filesystem::is_readable($directory .'/'. $filename);
					$file['writeable']=OC_Filesystem::is_writeable($directory .'/'. $filename);
					$file['type']=OC_Filesystem::filetype($directory .'/'. $filename);
					if($file['type']=='dir'){
						$dirs[$file['name']]=$file;
					}else{
						$files[$file['name']]=$file;
					}
				}
			}
			closedir($dh);
			}
		}
		uksort($dirs, "strnatcasecmp");
		uksort($files, "strnatcasecmp");
		$content=array_merge($dirs,$files);
		if($filesfound){
			return $content;
		}else{
			return false;
		}
	}



	/**
	* return the content of a file or return a zip file containning multiply files
	*
	* @param dir  $dir
	* @param file $file ; seperated list of files to download
	*/
	public static function get($dir,$files){
		if(strpos($files,';')){
			$files=explode(';',$files);
		}

		if(is_array($files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			foreach($files as $file){
				$file=$dir.'/'.$file;
				if(OC_Filesystem::is_file($file)){
					$tmpFile=OC_Filesystem::toTmpFile($file);
					self::$tmpFiles[]=$tmpFile;
					$zip->addFile($tmpFile,basename($file));
				}elseif(OC_Filesystem::is_dir($file)){
					zipAddDir($file,$zip);
				}
			}
			$zip->close();
		}elseif(OC_Filesystem::is_dir($dir.'/'.$files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			$file=$dir.'/'.$files;
			zipAddDir($file,$zip);
			$zip->close();
		}else{
			$zip=false;
			$filename=$dir.'/'.$files;
		}
		if($zip or OC_Filesystem::is_readable($filename)){
			header('Content-Disposition: attachment; filename="'.basename($filename).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			if($zip){
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($filename));
			}else{
				header('Content-Type: ' . OC_Filesystem::getMimeType($filename));
				header('Content-Length: ' . OC_Filesystem::filesize($filename));
			}
		}elseif($zip or !OC_Filesystem::file_exists($filename)){
			header("HTTP/1.0 404 Not Found");
			$tmpl = new OC_Template( '', '404', 'guest' );
			$tmpl->assign('file',$filename);
			$tmpl->printPage();
// 			die('404 Not Found');
		}else{
			header("HTTP/1.0 403 Forbidden");
			die('403 Forbidden');
		}
		@ob_end_clean();
		if($zip){
			readfile($filename);
			unlink($filename);
		}else{
			OC_Filesystem::readfile($filename);
		}
		foreach(self::$tmpFiles as $tmpFile){
			if(file_exists($tmpFile) and is_file($tmpFile)){
				unlink($tmpFile);
			}
		}
	}

	/**
	* move a file or folder
	*
	* @param dir  $sourceDir
	* @param file $source
	* @param dir  $targetDir
	* @param file $target
	*/
	public static function move($sourceDir,$source,$targetDir,$target){
		if(OC_User::isLoggedIn()){
			$targetFile=$targetDir.'/'.$target;
			$sourceFile=$sourceDir.'/'.$source;
			return OC_Filesystem::rename($sourceFile,$targetFile);
		}
	}

	/**
	* copy a file or folder
	*
	* @param dir  $sourceDir
	* @param file $source
	* @param dir  $targetDir
	* @param file $target
	*/
	public static function copy($sourceDir,$source,$targetDir,$target){
		if(OC_User::isLoggedIn()){
			$targetFile=$targetDir.'/'.$target;
			$sourceFile=$sourceDir.'/'.$source;
			return OC_Filesystem::copy($sourceFile,$targetFile);
		}
	}

	/**
	* create a new file or folder
	*
	* @param dir  $dir
	* @param file $name
	* @param type $type
	*/
	public static function newFile($dir,$name,$type){
		if(OC_User::isLoggedIn()){
			$file=$dir.'/'.$name;
			if($type=='dir'){
				return OC_Filesystem::mkdir($file);
			}elseif($type=='file'){
				$fileHandle=OC_Filesystem::fopen($file, 'w');
				if($fileHandle){
					fclose($fileHandle);
					return true;
				}else{
					return false;
				}
			}
		}
	}

	/**
	* deletes a file or folder
	*
	* @param dir  $dir
	* @param file $name
	*/
	public static function delete($dir,$file){
		if(OC_User::isLoggedIn()){
			$file=$dir.'/'.$file;
			return OC_Filesystem::unlink($file);
		}
	}

	/**
	* try to detect the mime type of a file
	*
	* @param  string  path
	* @return string  guessed mime type
	*/
	static function getMimeType($path){
		return OC_Filesystem::getMimeType($path);
	}

	/**
	* get a file tree
	*
	* @param  string  path
	* @return array
	*/
	static function getTree($path){
		return OC_Filesystem::getTree($path);
	}

	/**
	* pull a file from a remote server
	* @param  string  source
	* @param  string  token
	* @param  string  dir
	* @param  string  file
	* @return string  guessed mime type
	*/
	static function pull($source,$token,$dir,$file){
		$tmpfile=tempnam(sys_get_temp_dir(),'remoteCloudFile');
		$fp=fopen($tmpfile,'w+');
		$url=$source.="/files/pull.php?token=$token";
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);
		$info=curl_getinfo($ch);
		$httpCode=$info['http_code'];
		curl_close($ch);
		if($httpCode==200 or $httpCode==0){
			OC_Filesystem::fromTmpFile($tmpfile,$dir.'/'.$file);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * set the maximum upload size limit for apache hosts using .htaccess
	 * @param int size filesisze in bytes
	 */
	static function setUploadLimit($size){
		global $SERVERROOT;
		global $WEBROOT;
		$size=OC_Helper::humanFileSize($size);
		$size=substr($size,0,-1);//strip the B
		$size=str_replace(' ','',$size); //remove the space between the size and the postfix
		$content = "ErrorDocument 404 /$WEBROOT/core/templates/404.php\n";//custom 404 error page
		$content.= "php_value upload_max_filesize $size\n";//upload limit
		$content.= "php_value post_max_size $size\n";
		$content.= "SetEnv htaccessWorking true\n";
		$content.= "Options -Indexes\n";
		@file_put_contents($SERVERROOT.'/.htaccess', $content); //supress errors in case we don't have permissions for it
	}
}
