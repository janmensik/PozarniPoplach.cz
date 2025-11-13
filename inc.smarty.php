<?php
# ěščřžýáíéů

# Smarty templates
use Smarty\Smarty;

$Smarty = new Smarty();
$Smarty->setTemplateDir($LOCAL['smarty']['TEMPLATE_DIR']);
$Smarty->setConfigDir($LOCAL['smarty']['TEMPLATE_DIR']);
$Smarty->setCompileDir($LOCAL['smarty']['COMPILE_DIR']);


$Smarty->compile_check = $LOCAL['DEBUGGING'];
if ($LOCAL['DEBUGGING'] === 2)
	$Smarty->debugging = true;
$Smarty->error_reporting = E_ALL ^ E_WARNING;

# Smarty plugins
if (is_array($smarty_plugins))
	foreach ($smarty_plugins as $smarty_plugin => $smarty_type) {
		switch ($smarty_type) {
			case 'modifier':
				$smarty_type = Smarty::PLUGIN_MODIFIER;
				break;
			case 'function':
			default:
				$smarty_type = Smarty::PLUGIN_FUNCTION;
		}

		require_once(__DIR__ . '/lib/smarty-plugins/' . $smarty_type . '.' . $smarty_plugin . '.php');
		$Smarty->registerPlugin($smarty_type, $smarty_plugin, 'smarty_' . $smarty_type . '_' . $smarty_plugin);
	}
