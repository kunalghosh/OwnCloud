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
 */

// Init owncloud
require_once('../../../lib/base.php');
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/addcontact.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/addcontact.php: '.$msg, OC_Log::DEBUG);
}

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.$element);
}

$aid = $_POST['aid'];
OC_Contacts_App::getAddressbook( $aid ); // is owner access check

$fn = trim($_POST['fn']);
$n = trim($_POST['n']);
debug('N: '.$n);
debug('FN: '.$fn);

$vcard = new OC_VObject('VCARD');
$vcard->setUID();
$vcard->setString('FN',$fn);
$vcard->setString('N',$n);

$id = OC_Contacts_VCard::add($aid,$vcard);
if(!$id) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('There was an error adding the contact.'))));
	OC_Log::write('contacts','ajax/addcontact.php: Recieved non-positive ID on adding card: '.$id, OC_Log::ERROR);
	exit();
}

OC_JSON::success(array('data' => array( 'id' => $id )));
