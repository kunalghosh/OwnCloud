<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
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

$id = $_POST['id'];
$l10n = new OC_L10N('contacts');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
	exit();
}

$card = OC_Contacts_Addressbook::findCard( $id );
if( $card === false ){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('Can not find Contact!'))));
	exit();
}

$addressbook = OC_Contacts_Addressbook::findAddressbook( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('This is not your contact!'))));
	exit();
}

$vcard = Sabre_VObject_Reader::read($card['carddata']);

$name = $_POST['name'];
$value = $_POST['value'];
$parameters = isset($_POST['parameteres'])?$_POST['parameters']:array();

if(is_array($value)){
	$value = OC_Contacts_Addressbook::escapeSemicolons($value);
}
$property = new Sabre_VObject_Property( $name, $value );
$parameternames = array_keys($parameters);
foreach($parameternames as $i){
	$property->parameters[] = new Sabre_VObject_Parameter($i,$parameters[$i]);
}

$vcard->add($property);

$line = count($vcard->children) - 1;
$checksum = md5($property->serialize());

OC_Contacts_Addressbook::editCard($id,$vcard->serialize());

$tmpl = new OC_Template('contacts','part.property');
$tmpl->assign('property',OC_Contacts_Addressbook::structureProperty($property,$line));
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'page' => $page )));
