<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************
$APPD = AppData::getInstance();

# *******************************************************************
# PROGRAM
# *******************************************************************

$User->logout();

# permanent login discard
unset($_COOKIE['permanent_login']);
setcookie("permanent_login", "", time() - 3600, '/');

# session discard
unset($_SESSION['user']);
unset($_SESSION['user_id']);
//session_unset();
//session_destroy();

$APPD->MESSAGES['logout']['result'] = 'logout';
$APPD->hibernateMessages();

# redirect to home /
header('Location: ' . $APPD->getData('BASE_URL'));
header("Connection: close");
exit();
