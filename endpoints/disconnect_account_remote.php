<?php

$rest = new Ri_Rest();	
$cred = new Ri_Credentials();
if (!$cred->verifyConnection( Ri_Rest::POST('wp_user_id'), Ri_Rest::POST('timestamp'), Ri_Rest::POST('hash') )) {
	// hanky panky
	$rest->respond(Ri_ApiMessages::getMessage($cred));
}
$cred = new Ri_Credentials();
$cred->api_login = Ri_Rest::POST('api_login');
$ok = (bool) $cred->delete();

$rest->respond(array(
	'data' => array(
		'success' => $ok,
	)
));
