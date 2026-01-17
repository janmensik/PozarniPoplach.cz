<?php

# *******************************************************************
# NEEDS
# *******************************************************************

require_once(__DIR__ . '/inc.startup.php');

require_once(__DIR__ . '/lib/functions/function.getip.php'); # prevod "minuly mesic" na time interval
require_once(__DIR__ . '/lib/functions/function.parseFloat.php'); # prevod "minuly mesic" na time interval
require_once(__DIR__ . '/lib/functions/function.pagination.php'); # pagination


use Janmensik\Jmlib\Database;
// Alias AppData to global namespace for backward compatibility
class_alias(\Janmensik\Jmlib\AppData::class, 'AppData');
class_alias(\Janmensik\Jmlib\Modul::class, 'Modul');

require_once(__DIR__ . '/include/class.Version.php');
require_once(__DIR__ . '/include/class.User.php');

# *******************************************************************
# GLOBAL APPDATA
# *******************************************************************
$APPD = AppData::getInstance();

$APPD->setData('BASE_URL', $_ENV['ABSOLUTE_URL']);
$APPD->setData('APP', $_ENV);

# ...................................................................
# version info
if (!isset($Version))
    $Version = new Version();
$APPD->setData('APP_VERSION', $Version->getCurrentVersion());

# *******************************************************************
# Session and redirect handling
# *******************************************************************

session_name('pozarnipoplach');
session_start();

$APPD->loadMessages();

# *******************************************************************
# DEFINICE, INICIALIZACE
# *******************************************************************

date_default_timezone_set('Europe/Prague');

mb_internal_encoding("UTF-8");

# rucni debug (pouze pokud neni ostry provoz)
if ($_ENV['DEBUGGING'] == 1 && isset($_GET['debug']))
    $_ENV['DEBUGGING'] = 2;
$APPD->setData('DEBUG_MODE', $_ENV['DEBUGGING']);

# spusteni tridy Database
$DB = new Database($_ENV['SQL_HOST'], $_ENV['SQL_DATABASE'], $_ENV['SQL_USER'], $_ENV['SQL_PASSWORD']);
$DB->query('SET CHARACTER SET utf8;');

# Smarty templates
$smarty_plugins = array(
    //'czmonth' => 'modifier',
    //'czday' => 'modifier',
    'czech_num_items' => 'modifier',
    'nice_num' => 'modifier',
    'nl2br' => 'modifier',
    'agots' => 'function',
    'ppurl' => 'function',
    //'nl2p' => 'modifier',
    //'utf2ascii' => 'modifier',
    //'thumb' => 'function',
);
require_once(__DIR__ . "/lib/functions/class.url_parameters.php");
require_once(__DIR__ . '/inc.smarty.php');

# Smarty load global config
$Smarty->config_overwrite = false;
$Smarty->configLoad(__DIR__ . '/tpl/app.conf', 'pages');
$APPD->setData('CONFIG', $Smarty->getConfigVars());

# router
$router = new \Bramus\Router\Router();


# CASBIN access policy
use Casbin\Enforcer;

$CASBIN = new Casbin\Enforcer($_ENV['CASBIN_MODEL'], $_ENV['CASBIN_POLICY']);

# *******************************************************************
# LOGIN, LOGOUT & USER CHECK
# *******************************************************************

# establish User object
$User = new User($DB, $CASBIN);
if (isset($_SESSION['user_id'])) {
    $User->load($_SESSION['user_id']);

} else {
    # check for permanent login cookie
    if (isset($_COOKIE['permanent_login'])) {
        $user_id = $User->verifyPermanent($_COOKIE['permanent_login']);
        if ($user_id) {
            // $APPD->setData('USER', $User->load($user_id));
            $APPD->setData('USER', $User->getUser());
            $_SESSION['user_id'] = $user_id;

            $APPD->MESSAGES['saved']['login'] = 'logged';
        }
    }
}

# login check
if ($User->getUser()) {
    # load up access policy (CASBIN)
}

// # *******************************************************************
// # GLOBAL stuff
// # *******************************************************************

// # ###################################################################

// /* FILTERS */
// if (is_array($FILTERS[getContent()]))
// 	foreach ($FILTERS[getContent()] as $value)
// 		if ($_GET[$value]) {
// 			$Smarty->assign('FILTERS_ACTIVE', true);
// 			break;
// 		}
// $Smarty->assign('FILTERS', $FILTERS);


# *******************************************************************
# router
# *******************************************************************

require_once(__DIR__ . "/include/routes.php");

# Run router on routes
$router->run();

# global check of not logged user (and not login) - redirect to login
if (/*$APPD->getData('ERROR') == '403' &&*/!$User->getUser() && $APPD->getData('PAGE') != 'login') {
    header('Location: ' . $APPD->getData('BASE_URL') . '/login');
    header("Connection: close");
    exit();
}

# *******************************************************************
# FINAL assign and Smarty template run
# *******************************************************************

$Smarty->assign('SESSION', $_SESSION);
$Smarty->assign('MESSAGES', $APPD->getMessages());

$Smarty->assign('USER', $User->getUser());

# common access - short names
$Smarty->assign('PAGE', $APPD->getData('PAGE'));
$Smarty->assign('BASE_URL', $APPD->getData('BASE_URL'));
$Smarty->assign('APP_VERSION', $APPD->getData('APP_VERSION'));
$Smarty->assign('ERROR', $APPD->getData('ERROR'));


# all APPD vars.
$Smarty->assign('APPD', $APPD->getData());

$Smarty->assign('FILTERS', $APPD->getFilters($APPD->getData('PAGE')));

$Smarty->assign('DEBUG_sql_queries', $DB->messages);

# prefix
if ($APPD->getData('TYPE') == 'controller')
    $template_prefix = 'ctrl';
else
    $template_prefix = 'page';

if ($APPD->getData('API')) {
	header('Content-Type: application/json');
	header('Content-Encoding: UTF-8');
	header('Content-language: cs');
} else
    $Smarty->display($template_prefix . '.' . $APPD->getData('PAGE') . '.html');

$APPD->clearMessages();