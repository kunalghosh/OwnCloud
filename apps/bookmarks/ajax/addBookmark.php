<?php

/**
* ownCloud - bookmarks plugin
*
* @author Arthur Schiwon
* @copyright 2011 Arthur Schiwon blizzz@arthur-schiwon.de
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

//no apps or filesystem
$RUNTIME_NOSETUPFS=true;

require_once('../../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
	$_ut = "strftime('%s','now')";
} else {
	$_ut = "UNIX_TIMESTAMP()";
}

//FIXME: Detect when user adds a known URL
$query = OC_DB::prepare("
	INSERT INTO *PREFIX*bookmarks
	(url, title, description, user_id, public, added, lastmodified)
	VALUES (?, ?, ?, ?, 0, $_ut, $_ut)
	");
	
	
$params=array(
	htmlspecialchars_decode($_GET["url"]),
	htmlspecialchars_decode($_GET["title"]),
	htmlspecialchars_decode($_GET["description"]),
	OC_User::getUser()
	);
$query->execute($params);
$b_id = OC_DB::insertid();

if($b_id !== false) {
	$query = OC_DB::prepare("
		INSERT INTO *PREFIX*bookmarks_tags
		(bookmark_id, tag)
		VALUES (?, ?)
		");
		
	$tags = explode(' ', urldecode($_GET["tags"]));
	foreach ($tags as $tag) {
		if(empty($tag)) {
			//avoid saving blankspaces
			continue;
		}
		$params = array($b_id, trim($tag));
	    $query->execute($params);
	}
}

