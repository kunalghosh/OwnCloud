<?php

OC_APP::register(array(
    "order" => 11,
    "id" => "fileSync",
    "name" => "Sync everything"));

OC_APP::addNavigationEntry(array(
    "id" => "fileSync_editor",
    "order" => 10,
    "href" => OC_HELPER::linkTo("fileSync", "editor.php"),
    "icon" => OC_HELPER::imagePath("fileSync", "output4.png"),
    "name" => "fileSync"));

#OC_APP::addSettingsPage(array(
#    "id" => "fileSync_settings",
#    "order" => 11,
#    "href" => OC_HELPER::linkTo("fileSync", "settings.php"),
#    "name" => "fileSync",
#    "icon" => OC_HELPER::imagePath("fileSync", "icon.png")));
?>
