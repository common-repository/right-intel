<?php

$wp_post_id = $_GET['wp_post_id'];
if ($wp_post_id == 'done') {
	update_option('right_intel_thumbnails_v3_8', "1");
	ob_clean();
	die('');
}
$image_attachment_id = Ri_Thumbnails::convert($wp_post_id);
list ($src, $width, $height) = wp_get_attachment_image_src( $image_attachment_id );

ob_clean();
header('Content-type: application/json; charset=utf-8');
echo json_encode(compact('src','width','height'));		
die();
