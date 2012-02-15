<?php
/**
* Copyright (c) 2011 Marvin Thomas Rabe <m.rabe@echtzeitraum.de>
* Copyright (c) 2011 Arthur Schiwon <blizzz@arthur-schiwon.de>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

OC::$CLASSPATH['OC_Bookmarks_Bookmarks'] = 'apps/bookmarks/lib/bookmarks.php';

OC_App::register( array( 'order' => 70, 'id' => 'bookmark', 'name' => 'Bookmarks' ));

$l = new OC_l10n('bookmarks');
OC_App::addNavigationEntry( array( 'id' => 'bookmarks_index', 'order' => 70, 'href' => OC_Helper::linkTo( 'bookmarks', 'index.php' ), 'icon' => OC_Helper::imagePath( 'bookmarks', 'bookmarks.png' ), 'name' => $l->t('Bookmarks')));

OC_App::registerPersonal('bookmarks', 'settings');
require_once('apps/bookmarks/lib/search.php');
OC_Util::addScript('bookmarks','bookmarksearch');
