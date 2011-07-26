<?php

/* This is the main script of the vcf web interface editor
 * 
 * It handles the templating, provide vars to the scripts in /templates, and recieve vars from them
 * 
 * It must me adapted to use the OC api for handling files
 * 
 */

require_once('../../lib/base.php');
require( 'template.php' );


//Also check if the user is logged
// Check if we are a user
if (!OC_USER::isLoggedIn()) {
    header("Location: " . OC_HELPER::linkTo('', 'index.php'));
    exit();
}





OC_APP::setActiveNavigationEntry("syncPimData_editor");



//use OC api to retrieve that info 
$contacts_dir = $CONFIG_DATADIRECTORY;

//echo $contacts_dir;

if (!(isset($_GET['mode']))) {
    $mode = "list";
} else {

    $mode = $_GET['mode'];
}

if ($mode == "list") {



    //if rawData exists, i've came from view.php
    if (isset($_SESSION['rawData'])) {



        //no we check what button we've pressed, delete or done
        if (!empty($_POST['done'])) {



            //Done button have been pressed. Chech if there are differences between the original data (retrieved by _session) and
            //the data provided by the form
            foreach ($_SESSION['rawData'] as $key => $value) {

                if ($_POST[$key] != $value) {

                    $_SESSION['rawData'][$key] = $_POST[$key];


                    $changed = true;
                }

                //we encode the value in the original way
                $value = quoted_printable_encode($value);
            }

            if ($changed == true) {

                //save the file (must be done with OC API)
                save_rawDataArray($_POST['url'], $_SESSION['rawData']);
            }
        } elseif (!empty($_POST['delete'])) {
            //The delete button has been pressed. We must use the OC api instead
            unlink($_POST['url']);
        }
    }


    $tmpl = new OC_TEMPLATE("fileSync", "list", "user");
    $tmpl->assign("dir", $contacts_dir);
} elseif ($mode == "view") {

    $tmpl = new OC_TEMPLATE("fileSync", "view", "user");
    $url = $contacts_dir . $_GET['contact'];
    $tmpl->assign("url", $url);
}

unset($_SESSION['rawData']);
$tmpl->printPage();

//saves the data stored in vcardarray into the provided url

function save_rawDataArray($url, $rawData) {

    $string = "";

    foreach ($rawData as $key => $value) {

        $string = $string . $key . ':' . $value . PHP_EOL;
    }

    file_put_contents($url, $string);


    //Now, probably I should update the state file with the new hash and the new anchor. I'm not sure of that
}

?>