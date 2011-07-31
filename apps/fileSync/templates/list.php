<?php

//This script shows all the card files of a user
//Also, a better integration for OC is needed. (Using the OC api for file handling). Also check if the user is logged
//Those two vars must be provided!

$dir = $_['dir'];
$contacts = scandir($dir);

//echo $dir;


echo "<ul>";
foreach ($contacts as $contact) {


    //$contact = base64_decode($rawContact);
/*
    echo "<br>";
    echo $contact;
    echo "<br>";
    //echo $rawContact;
*/
    if (($contact != ".") AND ($contact != "..")) {
        if (get_readable_name($dir.'/' . $contact) != "") {
            echo '<li><a href="editor.php?contact=' . $contact . '&mode=view">' . get_readable_name($dir.'/' . $contact) . '</a></li>';
        }
    }
}
echo "</ul>";

function get_readable_name($url) {

    $vcard = file($url, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    $auxName;



    foreach ($vcard as $line) {
        list($rawKey, $value) = split(":", $line, 2);
        list($key, $rest) = split(';', $rawKey, 2);
        if ($key == "FN") {
            return quoted_printable_decode($value);
        }

        if ($key == "EMAIL") {
            $auxName = quoted_printable_decode($value);
        }
    }
    return $auxName;
}

?>