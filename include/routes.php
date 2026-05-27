<?php

$APPD = AppData::getInstance();

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
$router->get('/logout', function () use ($Smarty, $DB, $User, $CASBIN) {
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
$router->post('/login', function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/login.php');
});

# *******************************************************************

# version history
$router->get('/version-history', function () use ($Smarty, $DB, $User, $CASBIN) {
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
$router->get('/' . $APPD->data['CONFIG']['users_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/users.php');
});

$router->get('/' . $APPD->data['CONFIG']['users_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});
$router->post('/' . $APPD->data['CONFIG']['users_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/settings.php');
});

# *******************************************************************

# search - quick switch to reservation
$router->get('/' . $APPD->data['CONFIG']['search_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/search.php');
});

# *******************************************************************
/*
# reservation - edit
$router->match('GET|POST','/'.$APPD->data['CONFIG']['reservations_url'].'/([0-9]{1,8})/edit', function ($pincode) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/reservation-edit.php');
});

# reservation -  mailer
$router->post('/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/mailer', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/controller/reservation.mailer.php');
});

# reservation -  status change
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/change', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/controller/reservation.change.php');
});

# reservation -  payment
$router->post('/' . $APPD->data['CONFIG']['reservations_url'] . '/([0-9]{1,8})/pay', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/controller/reservation.payment.php');
});
*/

# *******************************************************************

# units - list
$router->get('/' . $APPD->data['CONFIG']['units_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/units.php');
});

# unit - detail, edit

$router->match('GET|POST', '/' . $APPD->data['CONFIG']['units_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/unit-edit.php');
});

# *******************************************************************

# dispatches - list
$router->get('/' . $APPD->data['CONFIG']['dispatches_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/dispatches.php');
});

# dispatch - detail

// $router->match('GET|POST', '/' . $APPD->data['CONFIG']['dispatches_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
//     include('./view/page/dispatch-edit.php');
// });

# *******************************************************************

# Devices - list
$router->get('/' . $APPD->data['CONFIG']['devices_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/devices.php');
});

# device - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['devices_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/device-edit.php');
});

# *******************************************************************

# vehicle types - list
$router->get('/' . $APPD->data['CONFIG']['vehicle_types_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/vehicle-types.php');
});

# vehicle type - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['vehicle_types_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/vehicle-type-edit.php');
});

# *******************************************************************

# event types - list
$router->get('/' . $APPD->data['CONFIG']['event_types_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/event-types.php');
});

# event type - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['event_types_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/event-type-edit.php');
});

# *******************************************************************

# Advertisers - list
$router->get('/' . $APPD->data['CONFIG']['advertisers_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/advertisers.php');
});

# Advertiser - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['advertisers_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/advertiser-edit.php');
});

# *******************************************************************

# Ads - list
$router->get('/' . $APPD->data['CONFIG']['ads_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/ads.php');
});

# Ad - detail, edit
$router->match('GET|POST', '/' . $APPD->data['CONFIG']['ads_url'] . '/([0-9]{1,8}|new)', function ($id) use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/ad-edit.php');
});

# *******************************************************************

# mail schedule - list
$router->get('/' . $APPD->data['CONFIG']['mail_schedule_url'], function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/mail-schedule.php');
});

# *******************************************************************

# index (dashboard)
$router->get('/', function () use ($Smarty, $DB, $User, $CASBIN) {
    include('./view/page/dashboard.php');
});

# *******************************************************************

# API
$router->mount('/api', function () use ($router, $DB, $User, $Smarty) {

    $router->get('/get-price', function () use ($DB, $User) {
        include('./view/api/get-price.php');
    });

    $router->get('/alarm', function () use ($DB, $User) {
        include('./view/api/get-alarm.php');
    });
});
