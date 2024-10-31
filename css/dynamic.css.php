<?php

// required options in GET:
// color_text - hex or rgba of bubble text color
// color_bubble - hex or rgba  of bubble background color
// image_display - either "none" or "block"
// use_oswald - a string number representing which font to use
// bubble_type - either "css" or "image"
// cachebust - the date of the last option change

$findReplace = array();
$findReplace['RI_DOMAIN'] = getenv("RI_DOMAIN") ?: 'https://sharpr.com';

$findReplace['color_text'] = (@$_GET['color_text'] ?: '#ffffff');

$color_bubble = (@$_GET['color_bubble'] ?: '#f26522');
$findReplace['color_bubble_url'] = ltrim($color_bubble, '#');
$findReplace['color_bubble'] = $color_bubble;

if ((@$_GET['image_display_type'] ?: 'both') == 'thumbnail_only') {
	$findReplace['image_display'] = 'none';
}
else {
	$findReplace['image_display'] = 'block';
}
if (@$_GET['use_oswald'] === '0') {
	$findReplace['font_face'] = 'inherit';
}
elseif (@$_GET['use_oswald'] === '2') {
	$findReplace['font_face'] = 'arvoitalic,"Times New Roman",Times,serif';
}
elseif (@$_GET['use_oswald'] === '3') {
	$findReplace['font_face'] = 'Georgia,"Times New Roman",Times,serif !important; font-style: italic';
}
else {
	$findReplace['font_face'] = 'OswaldRegular,Helvetica,Arial,sans-serif';
}

$include = array(
	'dynamic-bubble.css',
	'dynamic-bubble-' . (@$_GET['bubble_type'] == 'css3' ? 'css3' : 'image') . '.css',
	'dynamic-image-position.css',
);
if (@$_GET['use_oswald'] === '1') {
	array_unshift($include, 'dynamic-oswald.css');
}
elseif (@$_GET['use_oswald'] === '2') {
	array_unshift($include, 'dynamic-arvo.css');
}
$css = '';
foreach ($include as $name) {
	$css .= file_get_contents(__DIR__ . "/$name");
}
$css = str_replace(array_keys($findReplace), array_values($findReplace), $css);
if (@$_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	$css = compressCss($css);
}

header('Cache-Control: max-age=31556926');
header('ETag: "'.substr(md5(@$_GET['cachebust']), 0, 16).'"');
header('Content-type: text/css; charset=utf-8');
echo $css;
die;

function compressCss($css) {
	$css = preg_replace('~/\*.*?\*/~s','',$css); // comments
	$css = str_replace(array("\n","\t","\r"),'',$css); // newlines and tabs
	$css = preg_replace('/: */',':',$css); // space after colons
	$css = preg_replace('/ *(;|\{|\}) */','$1',$css); // space before/after semicolons and braces
	$css = str_replace(';}','}',$css); // remove final semicolon
	$css = trim($css);
	return $css;
}