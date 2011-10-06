<?php

//This template recieves the url of a vcf card file, and shows it in a editable way. The modifications can be saved pressing
//some kind of "save" button. Alse there's a "delete" button
//The file handling must be done with OC api
//require 'states.php';
//Also check if the user is logged


$vcard = file($_['url'], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);


echo

'<html>
<body>


<form name="done" action="editor.php?mode=list" method="post">';


foreach ($vcard as $line) {
    list($rawKey, $value) = split(":", $line, 2);
    $key = readable_key($rawKey);
    $rawData[$rawKey] = quoted_printable_decode($value);
    if ($key != false) {

        //It's data interesting for the user

        echo $key . ': <input type="text" name="' . $rawKey . '" value="' . quoted_printable_decode($value) . '" />';
    } else {

        //Non user-friendly data

        echo '<input type="hidden" name="' . $rawKey . '" value="' . quoted_printable_decode($value) . '" />';
    }
}

echo '<input type="hidden" name="url" value="' . $_['url'] . '" />


<input type="hidden" name="done" value="true" />    

<input type="submit" value="Done" />


</form>

<form name="delete" action="editor.php?mode=list" method="post">
<input type="hidden" name="url" value="' . $_['url'] . '" />
<input type="hidden" name="delete" value="true" />
<input type="submit" value="Delete contact" />
</form>



</body>
</html> ';



$_SESSION['rawData'] = $rawData;

//This function retrieves an ugly key, and transforms it into a readable key
function readable_key($rawKey) {

    list($key, $rest) = split(';', $rawKey, 2);

    switch ($key) {
        case "EMAIL":
            return $value = "Email";
            break;
        case "FN":
            return $value = "Name";
            break;
        case "TEL":
            switch ($rest) {
                case "TYPE=CELL":
                    return $value = "Mobile number";
                    break;
                case "TYPE=HOME":
                    return $value = "Home number";
                    break;
            }
            break;
        default:
            return $value = false;
    };
}

;
?>
