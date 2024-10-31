<?php

class Ri_Curl {
	
	public static function getContents($url, $post = array(), $moreOpts = array()) {
		$post['RI_VERSION'] = RI_VERSION;
		$ch = curl_init();
		if (!empty($post)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post, null, '&'));
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		foreach ($moreOpts as $const => $value) {
			curl_setopt($ch, $const, $value);
		}
		$contents = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode != 200) {
			trigger_error("HTTP $httpCode - Ri_Curl::getcontents('$url',\$post,\$moreOpts)", E_USER_WARNING);
			$contents = '';
		}
		elseif ($contents === false) {
			trigger_error("cURL failure - Ri_Curl::getContents('$url',\$post,\$moreOpts)", E_USER_WARNING);	
		}
		elseif ($contents === '') {
			trigger_error("Zero bytes received - Ri_Curl::getContents('$url',\$post,\$moreOpts)", E_USER_WARNING);	
		}
		curl_close($ch);
		return $contents;
	}
	
}