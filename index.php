<?php

/*
Plugin Name: Sharpr
Plugin URI https://wordpress.org/plugins/right-intel/
Description: The Sharpr Wordpress Plugin allows you to push posts from the Sharpr application to your WordPress blog. (Sharpr is formerly Right Intel)
Version: 4.4.2
Author: kendsnyder
Author URI: http://sharpr.com/home
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if (version_compare(PHP_VERSION, '5.3', '>=')) {
	require_once( __DIR__ . '/right-intel.php' );
}
else {
	// notify that right-intel plugin requires php 5.3
	trigger_error('The Sharpr plugin requires PHP 5.3 or higher. Please contact your server administrator to upgrade your PHP version.', E_USER_WARNING);
}
