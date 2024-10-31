<?php

Ri_Rest::run( 'ri_get_publish_options' );

function ri_get_publish_options( Ri_Rest $rest ) {
	// https://codex.wordpress.org/Option_Reference
	$blog_name = get_option('blogname');
	$home_url = get_option('home');
	// if duplicate, get url of existing post
	$post = new Ri_Post((object) Ri_Rest::POST());
	$duplicate_url = $post->getPermalinkByRiPostId();
	// see http://codex.wordpress.org/Function_Reference/get_categories
	$raw_categories = get_categories(array(
		'hide_empty' => 0
	));
	$post_category = array();
	foreach ($raw_categories as $cat) {
		$post_category[] = array(
			'id' => $cat->term_id,
			'name' => $cat->name,
			'parent_id' => $cat->parent
		);
	}	
	// http://codex.wordpress.org/Function_Reference/get_tags
	$raw_tags = get_tags(array(
		'hide_empty' => 0,
	));
	$tags_input = array();
	foreach ($raw_tags as $tag) {
		$tags_input[] = array(
			'id' => $tag->term_id,
			'name' => html_entity_decode($tag->name, ENT_QUOTES, 'UTF-8'),
			'count' => (int) $tag->count,
		);
	}
	$most_used_tags = $tags_input;
	usort($most_used_tags, function($a, $b) {
		return $b['count'] - $a['count'];
	});
	$most_used_tags = array_slice($most_used_tags, 0, 20);
	$comment_status = array(
		'label' => 'Comments',
		'options' => array(
			'open' => 'Allow readers to post comments',
			'closed' => "Don't allow comments",
		),
		'default' => get_option('default_comment_status')
	);
	$ping_status = array(
		'label' => 'Trackbacks',
		'options' => array(
			'open' => 'Allow pingbacks and trackbacks',
			'closed' => "Don't allow trackbacks",
		),
		'default' => get_option('default_ping_status')
	);
	$post_status = array(
		'label' => 'Publish status',
		'options' => array(
			'draft' => 'Draft',
			'pending' => 'Pending review',
			'publish' => 'Published',
		),
		'default' => 'publish'
	);
	$author_list = get_users(array(
		'who' => 'authors'
	));
	$authors = array();
	foreach ($author_list as $a) {
		$authors[$a->ID] = $a->display_name;
	}
	$css = plugins_url("/css/" . Ri_Styling::getCssRelativeUrl(), RI_BASE_PAGE);
	// TODO: get current author based on api_login
	$post_author = $rest->auth->wp_user_id;
	// is yoast seo plugin enabled
	$yoast = false;
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if (is_plugin_active('wordpress-seo/wp-seo.php')) {
		$yoast = array(
			'_yoast_wpseo_canonical' => array(
				'label' => 'Use source link as Canonical link',
				'options' => array(
					'0' => 'no',
					'1' => 'yes'				
				),
				'default' => '',
			),
			'_yoast_wpseo_meta-robots-noindex' => array(
				'label' => 'Meta Robots Index',
				'options' => array(
					'0' => 'default',
					'2' => 'index',
					'1' => 'noindex'				
				),
				'default' => '',
			),
			'_yoast_wpseo_meta-robots-nofollow' => array(
				'label' => 'Meta Robots Follow',
				'options' => array(
					'0' => 'follow',
					'1' => 'nofollow'
				),
				'default' => '',
			),
		);
	}
	return array(
		'data' => compact(
			'blog_name',
			'home_url',
			'duplicate_url',
			'authors',
			'post_category',
			'tags_input',
			'most_used_tags',
			'comment_status',
			'ping_status',
			'post_status',
			'post_author',
			'yoast',
			'css'
		)
	);
}
