<?php

# *******************************************************************
# routes
# *******************************************************************

# 404
$router->set404(function () {
    header('HTTP/1.1 404 Not Found');
    $APPD = AppData::getInstance();
    $APPD->setData('PAGE', '404');
});

# *******************************************************************

# logout
$router->get('/logout', function () use ($Smarty, $DB, $User) {
    include('./view/page/logout.php');
});

# *******************************************************************

# login
$router->get('/login', function () use ($Smarty, $DB, $CASBIN) {
    $APPD = AppData::getInstance();
    $APPD->setData('PAGE', 'login');
});

# *******************************************************************

# login
$router->post('/login', function () use ($Smarty, $DB) {
    include('./view/page/login.php');
});

# *******************************************************************

# version history
$router->get('/version-history', function () use ($Smarty, $DB, $User) {
    include('./view/page/version-history.php');
});

# *******************************************************************

# settings - users edit
$router->get('/' . $APPD->data['CONFIG']['settings_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});
$router->post('/' . $APPD->data['CONFIG']['settings_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});

# users - list
$router->get('/' . $APPD->data['CONFIG']['users_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/users.php');
});

$router->get('/' . $APPD->data['CONFIG']['users_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});
$router->post('/' . $APPD->data['CONFIG']['users_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});

# *******************************************************************

# transfer - list
$router->get('/' . $APPD->data['CONFIG']['transfers_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/transfers.php');
});

# *******************************************************************

# search - quick switch to reservation
$router->get('/' . $APPD->data['CONFIG']['search_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/search.php');
});

# *******************************************************************

# reservations - list
$router->get('/' . $APPD->data['CONFIG']['reservations_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/reservations.php');
});

# reservation -  detail
$router->get('/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})', function ($id) use ($Smarty, $DB, $User) {
    include('./view/page/reservation.php');
});

# reservation - new
$router->match('GET|POST','/'.$APPD->data['CONFIG']['reservations_url'].'/new', function () use ($Smarty, $DB, $User) {
    include('./view/page/reservation-new.php');
});

# reservation - edit
// $router->match('GET|POST','/'.$APPD->data['CONFIG']['reservations_url'].'/([0-9]{1,8})/edit', function ($pincode) use ($Smarty, $DB, $User) {
//     include('./view/page/reservation-edit.php');
// });

# reservation -  mailer
$router->post('/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/mailer', function ($id) use ($Smarty, $DB, $User) {
    include('./view/controller/reservation.mailer.php');
});

# reservation -  status change
$router->match('GET|POST','/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/change', function ($id) use ($Smarty, $DB, $User) {
    include('./view/controller/reservation.change.php');
});

# reservation -  payment
$router->post('/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/pay', function ($id) use ($Smarty, $DB, $User) {
    include('./view/controller/reservation.payment.php');
});

# *******************************************************************

# partners - list
$router->get('/' . $APPD->data['CONFIG']['partners_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/partners.php');
});

# partner - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['partners_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User) {
    include('./view/page/partner-edit.php');
});

# *******************************************************************

# pricelists - list
$router->get('/' . $APPD->data['CONFIG']['pricelists_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/pricelists.php');
});

# pricelist - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['pricelists_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User) {
    include('./view/page/pricelist-edit.php');
});

# *******************************************************************

# Gatecodes - list
$router->get('/' . $APPD->data['CONFIG']['gatecodes_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/gatecodes.php');
});

# Gatecode - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['gatecodes_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User) {
    include('./view/page/gatecode-edit.php');
});

# *******************************************************************

# Park Closures - list + quick edit
$router->get('/' . $APPD->data['CONFIG']['closures_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/closures.php');
});

# *******************************************************************

# mail schedule - list
$router->get('/' . $APPD->data['CONFIG']['mail_schedule_url'], function () use ($Smarty, $DB, $User) {
    include('./view/page/mail-schedule.php');
});

# *******************************************************************

# index (dashboard)
$router->get('/', function () use ($Smarty, $DB, $User) {
    include('./view/page/dashboard.php');
});

# *******************************************************************

# API
$router->mount('/api', function () use ($router, $DB, $User) {

    $router->get('/get-price', function () use ($DB, $User) {
        include('./view/api/get-price.php');
    });
});

# *******************************************************************

# ALARM
$router->mount('/alarm', function () use ($router, $DB, $Smarty, $APPD) {
    $router->get('/' . $APPD->data['CONFIG']['alarm_url'], function () use ($Smarty, $DB) {
        include('./view/page/alarm-dispatch.php');
    });
});