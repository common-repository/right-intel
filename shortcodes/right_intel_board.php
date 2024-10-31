<?php

$exports = function($attr) {
	if (empty($attr['url'])) {
		return 'Shortcode right-intel-board requires url attribute.';
	}
	$html = Ri_Curl::getContents($attr['url']);
	return $html;
};
