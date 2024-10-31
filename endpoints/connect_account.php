<?php

$rest = new Ri_Rest();	
$cred = new Ri_Credentials();
if (!$cred->verifyConnection( Ri_Rest::POST('wp_user_id'), Ri_Rest::POST('timestamp'), Ri_Rest::POST('hash') )) {
	// hanky panky
	$rest->respond(Ri_ApiMessages::getMessage($cred));
}
$cred->wp_user_id = Ri_Rest::POST('wp_user_id');
$cred->instance_name = Ri_Rest::POST('instance_name');
$cred->generateLogin();
$ok = (bool) $cred->save();

if (
	$ok && 
	Ri_Rest::POST('color_bubble') &&
	(string) get_option('right_intel_has_connected_before') !== '1' && 
	!Ri_Styling::hasOption('color_bubble')
) {	
	Ri_Styling::update(array(
		'color_bubble' => Ri_Rest::POST('color_bubble')
	));
	update_option('right_intel_has_connected_before', '1');
}

$rest->respond(array(
	'data' => array(
		'api_login' => $cred->api_login,
		'api_password' => $cred->api_password,
		'home' => get_option('home'),
		'blogname' => get_option('blogname')
	)
));


