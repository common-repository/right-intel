<?php

$api_login = $_GET['account_id'];
if ($api_login) {
	$ok = Ri_Credentials::disconnect($api_login);
}
ob_end_clean();
header("Location: options-general.php?page=sharpr_settings");
exit(0);


