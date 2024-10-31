<?php

Ri_Rest::run( 'ri_add_post' );

function ri_add_post( Ri_Rest $rest ) {
	$post = new Ri_Post((object) Ri_Rest::POST());
//	if (!$post->validate()) {
//		return Ri_ApiMessages::getMessage($post));
//	}
	$post->save($rest);
	return array(
		'data' => array(
			'wp_post_id' => $post->data->id,
			'permalink' => get_permalink( $post->data->id ),
			// get_edit_post_link only works when you are logged in as an admin who is able to edit the post
			// 'edit_url' => get_edit_post_link( $post->data->id, '' ),
			'edit_url' => get_option('siteurl') . "/wp-admin/post.php?post={$post->data->id}&action=edit",
		)
	);
}
