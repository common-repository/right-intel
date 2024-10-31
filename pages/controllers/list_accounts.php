<?php

$msgs = array();
$numConnected = (int) @$_GET['connected'];
$numDisconnected = (int) @$_GET['disconnected'];
if ($numConnected > 0) {
	$msgs[] = 'Connected ' . $numConnected .' Sharpr instance' . ($numConnected == 1 ? '' : 's') . '.';
}
if ($numDisconnected > 0) {
	$msgs[] = 'Disconnected ' . $numDisconnected .' Sharpr instance' . ($numDisconnected == 1 ? '' : 's') . '.';
}

if (Ri_Upload::validateInstall() && Ri_Router::validateInstall()) {
	$validInstall = true;
	$creds = Ri_Credentials::findAll();
	$actionUrl = Ri_Credentials::getConnectionUrl();
	$connectionFields = Ri_Credentials::getConnectionFields();
}
else {
	$validInstall = false;
}

$hasConnectedBefore = (string) get_option('right_intel_has_connected_before') === '1';
if (!$hasConnectedBefore && count($creds) > 0) {
	$hasConnectedBefore = true;
	update_option('right_intel_has_connected_before', '1');
}

if (!empty($_POST)) {
	// wp's update_option returns false if item is not changed and on error 
	// so we can't differentiate an error from clicking save when nothing has changed
	// so we just always report settings were saved ok
	Ri_Styling::save($_POST);
	Ri_Flash::add('Settings saved ok.');
	Ri_Flash::output();
}
$styling = Ri_Styling::getStyleValues();
extract($styling);

$themeSupportsThumbnailAbovePost = current_theme_supports('post-thumbnails');
$previewUrl = $router->getPageUrl('post_preview') . '&' . Ri_Styling::getCssUrlQuery($_GET);