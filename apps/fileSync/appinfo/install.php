<?php

//Set up the config.xml file of PHPSyncML with the info of OC
require_once('../../../lib/base.php');



//It should set up the config.xml file with info provided by the oc api


if (file_exists('../config/config.xml')) {
    $configxml = simplexml_load_file('../config/config.xml');





    $configxml->config->base_dir = $CONFIG_DATADIRECTORY;



    echo $configxml->asXML('../config/config.xml');
} else {
    exit('Failed to open config.xml.');
}
?>