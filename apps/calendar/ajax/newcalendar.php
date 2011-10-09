<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
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
$calendar = array(
	'id' => 'new',
	'displayname' => '',
	'calendarcolor' => '',
);
$tmpl = new OC_Template('calendar', 'part.editcalendar');
$tmpl->assign('new', true);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();
?>
