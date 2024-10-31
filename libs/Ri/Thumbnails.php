<?php

class Ri_Thumbnails {
	
	public static function upgradeIfNeeded(Ri_Router $router) {
		if ( get_option( 'right_intel_thumbnails_v3_8' ) === "1" ) {
			return;
		}
		if ( ! Ri_Post::hasAnyRightIntelPosts() ) {
			update_option( 'right_intel_thumbnails_v3_8', "1" );
			return;
		}
		$pageUrl = $router->addPage( array(
			'function' => 'add_submenu_page',
			'page_title' => 'Regenerate Thumbnails',
			'menu_title' => 'Sharpr',
			'file' => 'regenerate_thumbnails'
		) );
		$router->addPage( array(
			'function' => 'add_submenu_page',
			'page_title' => 'Regenerate Thumbnail By Id',
			'menu_title' => 'Sharpr',
			'file' => 'regenerate_thumbnail_by_id'
		) );
		// only show notice if we are not on that page already
		if ( get_option( 'siteurl' ) . $_SERVER['REQUEST_URI'] != $pageUrl ) {			
			$router->addAdminNotice('Thank you for updating the Sharpr WordPress plugin. Please <a href="' . esc_html($pageUrl) . '">click here</a> to complete the upgrade process.');
		}
	}
	
	public static function findUnconverted() {
		global $wpdb;
		$sql = "SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = 'ri_post_id' AND post_id NOT IN (
					SELECT post_id 
					FROM $wpdb->postmeta
					WHERE meta_key = '_wp_attachment_metadata'
				)
				ORDER BY post_id";
		$allRiIds = $wpdb->get_col($sql);
		if (count($allRiIds) == 0) {
			return array();
		}
		$sql = "SELECT meta_value, post_id FROM $wpdb->postmeta
				WHERE meta_key = '_thumbnail_id' 
				  AND post_id IN (" . join(',', $allRiIds) . ")";
		$maybeDone = $wpdb->get_results($sql);
		if (count($maybeDone) == 0) {
			return $allRiIds;
		}		
		$maybeDoneLookup = array();
		foreach ($maybeDone as $r) {
			$maybeDoneLookup[$r->meta_value] = $r->post_id;
		}
		$sql = "SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = '_wp_attachment_metadata' 
				  AND post_id IN (" . join(',', array_keys($maybeDoneLookup)) . ")";
		$doneAttachmentIds = $wpdb->get_col($sql);
		$doneIds = array();
		foreach ($maybeDoneLookup as $attachmentId => $postId) {
			if (in_array($attachmentId, $doneAttachmentIds)) {
				$doneIds[] = $postId;
			}
		}
		$undoneIds = array_values(array_diff($allRiIds, $doneIds));
		return $undoneIds;
	}
	
	public static function registerRightIntelSize() {
		// done on install or when upgrading to right-intel plugin v3.8.0
		add_image_size('right-intel-post', $maxWidth=260, $maxHeight=1200, $crop=false);
	}	
	
    /* Example data from postmeta table:

    meta_id  post_id  meta_key                 meta_value
	---------------------------------------------------------------
    2817     2540     ri_post_id               21063939
    2818     2540     _thumbnail_id            2541
    2819     2541     _wp_attached_file        ri/post-21063939.jpg
	2820     2541     _wp_attachment_metadata  a:6:{s:5:"width";s:3:"900";s:6:"height";s:2:"46";s:14:"hwstring_small";s:22:"...}

    Note that early versions of the plugin only had 2817, and pre 3.8 versions had all but 2820
	*/
		
	public static function convert($post_id) {
		$meta = get_metadata('post', $post_id);
		if (!isset($meta['ri_post_id'])) {
			// not a Sharpr post   || already processed
			return false;
		}
		if (isset($meta['_thumbnail_id'])) {
			$attachment_meta = get_metadata('post', $meta['_thumbnail_id'][0]);
			if (isset($attachment_meta['_wp_attachment_metadata'])) {
				// already done!
				return $meta['_thumbnail_id'][0];
			}
		}
		/* EXAMPLE DATA from calling wp_upload_dir('ri')
		Array (
		  [path] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads/ri”
		  [url] => “http://rightintel/home/wp-content/uploads/ri”
		  [subdir] => “/ri”
		  [basedir] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads”
		  [baseurl] => “http://rightintel/home/wp-content/uploads”
		  [error] => false
		)
		 */
		$upload_dir = wp_upload_dir('ri');
		$post_data = get_post($post_id);
		$post_data->ri_post_id = $meta['ri_post_id'][0];
		if (isset($meta['_wp_attached_file'])) {
			// we are upgrading from 3.7+ where we have already set thumbnail id
			$post_data->existing_path = $upload_dir['path'] . '/' . $meta['_wp_attached_file'][0];
		}
		else {
			// we are an old version without thumbnails
			$post_data->existing_path = static::_findExistingFilePath($post_data, $upload_dir);
		}
		if (!file_exists($post_data->existing_path)) {
			unset($post_data->existing_path);
		}
		$post = new Ri_Post($post_data);
		$attachment_id = $post->processImages($post_data->ID);
		return $attachment_id;
	}
	
	protected function _findExistingFilePath($post_data, $upload_dir) {
		// search inside post_content for an image url
		$regex = '~ src="[^"]+(/post-\d+\.[^"]+)"~';
		if (preg_match($regex, $post_data->post_content, $match)) {
			return $upload_dir['path'] . $match[1];
		}
		return false;
	}
	
}
