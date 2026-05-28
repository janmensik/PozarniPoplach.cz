<?php

# vytvori nahodne heslo o delce $length
function createPassword($length = 5, $salt = 'tajna konstanta') {
    return (substr(sha1(time() . $salt), 0, $length));
}
