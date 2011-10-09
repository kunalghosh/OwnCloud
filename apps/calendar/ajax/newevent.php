<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('calendar');

$errarr = OC_Calendar_Object::validateRequest($_POST);
if($errarr){
	//show validate errors
	OC_JSON::error($errarr);
	exit;
}else{
	$cal = $_POST['calendar'];
	$vcalendar = OC_Calendar_Object::createVCalendarFromRequest($_POST);
	$result = OC_Calendar_Object::add($cal, $vcalendar->serialize());
	OC_JSON::success();
}
?>
