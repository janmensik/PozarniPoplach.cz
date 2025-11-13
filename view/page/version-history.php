<?php

if (!isset($Version))
	$Version = new Version();

# ...................................................................

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'version-history');

# ...................................................................
# Access control READ
if (!$User->hasPermission('version-history', 'read')) {		
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................

$Smarty->assign('data', $Version->versions);
