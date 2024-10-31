<?php

#
# define some RI_* constants
#
define('RI_VERSION', '20160209');
define('RI_SEMVER', '4.4.2');
define('RI_BASE_DIR', __DIR__);
define('RI_BASE_PAGE', __DIR__ . '/index.php');
define('RI_APP_URL', getenv("RI_DOMAIN") ?: 'https://sharpr.com');
ob_start(); // allow our plugin pages to redirect

#
# require classes in libs on demand
#
spl_autoload_register(function($classname) {
	$path = __DIR__ . '/libs/' . str_replace('_', '/', $classname) . '.php';
	if (is_file($path)) {
		require_once($path);
	}
});

#
# register our hooks
#

// allow us to add admin messages with Ri_Flash::add($msg, $class) where $class can be 'updated' or 'error'
add_action( 'admin_notices', array('Ri_Flash','output') );
// register install and uninstall
register_activation_hook( RI_BASE_PAGE , array('Ri_Schema','install') );
register_activation_hook( RI_BASE_PAGE , array('Ri_Upload','install') );
register_activation_hook( RI_BASE_PAGE , array('Ri_Upload','validateInstall') );
register_activation_hook( RI_BASE_PAGE , array('Ri_Router','validateInstall') );
register_activation_hook( RI_BASE_PAGE , array('Ri_Styling','setDefaults') );
register_deactivation_hook( RI_BASE_PAGE , array('Ri_Schema','uninstall') );
// set up admin pages, user pages, and API endpoints
$router = new Ri_Router();
// upgrade thumbnails if they have not been updated before
Ri_Thumbnails::upgradeIfNeeded($router);
// endpoints that can be accessed via $blog_url/right_intel/$file
$router->addEndpoint( 'receiver.php' );
$router->addEndpoint( 'get_publish_options.php' );
$router->addEndpoint( 'connect_account.php' );
$router->addEndpoint( 'disconnect_account_remote.php' );
$router->addPage( array(
	'function' => 'add_options_page',
	'page_title' => 'Sharpr Settings',
	'menu_title' => 'Sharpr',
	'file' => 'list_accounts',
) );
$router->addPage( array(
	'function' => 'add_submenu_page',
	'page_title' => 'Sharpr Disconnect Account',
	'menu_title' => 'Sharpr',
	'file' => 'disconnect_account'
) );
$router->addPage( array(
	'function' => 'add_submenu_page',
	'page_title' => 'Sharpr Post Preview',
	'menu_title' => 'Sharpr',
	'file' => 'post_preview'
) );
// plugin settings page
$router->addPluginListLink( 'Settings', 'options-general.php?page=sharpr_settings' );

$router->addUpgradeHandler( '3.8.0', function() {
	Ri_Styling::setDefaults();
	Ri_Schema::drop_table_ri_widgets();
	return true;
} );
$router->addUpgradeHandler( '3.8.4', function() {
	if (get_option('right_intel_has_connected_before') !== '1' && count(Ri_Credentials::findAll()) > 0) {		
		update_option('right_intel_has_connected_before', '1');
	}
	return true;
} );
$styling = new Ri_Styling();
$styling->setupCss();
$styling->setupJs();
$styling->setupBodyClass();
$styling->setupPostThumbnails();
$styling->addCss( 'init', 'shortcodes.css' );

$router->addShortcode( 'right_intel_board', array(
	'url' => null,
) );
$router->addShortcode( 'right_intel_feed', array(
	'url' => null,
	'img' => 'pin-150.jpg',
	'limit' => '3',
	'intel_maxlength' => '500',
	'headline_maxlength' => '161',
	'summary_maxlength' => '250',
	'template' => null,
) );
