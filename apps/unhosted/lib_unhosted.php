<?php

class OC_UnhostedWeb {
	public static function getValidTokens($ownCloudUser, $userAddress, $dataScope) {
		$user=OC_DB::escape($ownCloudUser);
		$userAddress=OC_DB::escape($userAddress);
		$dataScope=OC_DB::escape($dataScope);
		$query=OC_DB::prepare("SELECT token,appUrl FROM *PREFIX*authtoken WHERE user=? AND userAddress=? AND dataScope=? LIMIT 100");
		$result=$query->execute(array($user,$userAddress,$dataScope));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			error_log( $entry );
			die( $entry );
		}
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']]=$userAddress;
		}
		return $ret;
	}

	public static function getAllTokens() {
		$user=OC_User::getUser();
		$query=OC_DB::prepare("SELECT token,appUrl,userAddress,dataScope FROM *PREFIX*authtoken WHERE user=? LIMIT 100");
		$result=$query->execute(array($user));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			error_log( $entry );
			die( $entry );
		}
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']] = array(
				'appUrl' => $row['appurl'],
				'userAddress' => $row['useraddress'],
				'dataScope' => $row['datascope'],
			);
		}
		return $ret;
	}

	public static function deleteToken($token) {
		$user=OC_User::getUser();
		$token=OC_DB::escape($token);
		$query=OC_DB::prepare("DELETE FROM *PREFIX*authtoken WHERE token=? AND user=?");
		$result=$query->execute(array($token,$user));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			error_log( $entry );
			die( $entry );
		}
	}
	private static function addToken($token, $appUrl, $userAddress, $dataScope){
		$user=OC_User::getUser();
		$token=OC_DB::escape($token);
		$appUrl=OC_DB::escape($appUrl);
		$userAddress=OC_DB::escape($userAddress);
		$dataScope=OC_DB::escape($dataScope);
		$query=OC_DB::prepare("INSERT INTO *PREFIX*authtoken (`token`,`appUrl`,`user`,`userAddress`,`dataScope`) VALUES(?,?,?,?,?)");
		$result=$query->execute(array($token,$appUrl,$user,$userAddress,$dataScope));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			error_log( $entry );
			die( $entry );
		}
	}
	public static function createDataScope($appUrl, $userAddress, $dataScope){
		$token=uniqid();
		self::addToken($token, $appUrl, $userAddress, $dataScope);
		//TODO: input checking on $userAddress and $dataScope
		list($userName, $userHost) = explode('@', $userAddress);
		OC_Util::setupFS(OC_User::getUser());
		$scopePathParts = array('unhosted', 'webdav', $userHost, $userName, $dataScope);
		for($i=0;$i<=count($scopePathParts);$i++){
			$thisPath = '/'.implode('/', array_slice($scopePathParts, 0, $i));
			if(!OC_Filesystem::file_exists($thisPath)) {
				OC_Filesystem::mkdir($thisPath);
			}
		}
		return $token;
	}
}
