<?php
require_once(__DIR__ .'/../ppr.php');
class Ri_Post {
	
	const ERROR_MISSING_FIELD = 1;
	
	const ERROR_WORDPRESS = 2;
	
	const ERROR_DUPLICATE = 3;
	
	public $slug;
	
	public $error;
	
	public function __construct($data = null) {
		if (is_object($data)) {
			$this->data = $data;
		}
	}
	
	public function validate() {
		// check required info
		if (
			strlen($this->data->bubble_img) == 0 ||
			strlen($this->data->post_author) == 0 ||
			strlen($this->data->post_content) == 0 ||
			strlen($this->data->post_status) == 0 ||
			strlen($this->data->post_title) == 0 ||
			strlen($this->data->ri_post_id) == 0
		) {
			$this->error = self::ERROR_MISSING_FIELD;
			return false;
		}
		if ($this->isDuplicate()) {
			$this->error = self::ERROR_DUPLICATE;
			return false;
		}
		return true;
	}

	public function isDuplicate() {
		global $wpdb;
		$sql = "SELECT EXISTS (SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ri_post_id' AND meta_value = %s) as has_one";
		$rs = $wpdb->prepare($sql, $this->data->ri_post_id);
		$row = $wpdb->get_row($rs);
		return (bool) @$row->has_one;
	}
	
	public function save(Ri_Rest $rest) {
		// CONVERT SENT POST INTO WP FORMAT
		$data = array(
			'menu_order' => false,
			'comment_status' => isset($this->data->comment_status) ? $this->data->comment_status : null,
			'ping_status' => isset($this->data->ping_status) ? $this->data->ping_status : null,
			'post_author' => $this->data->post_author,
			'post_category' => isset($this->data->post_category) ? $this->data->post_category : null, // array of ids
			'post_content' => $this->data->post_content,
			'post_excerpt' => isset($this->data->post_excerpt) ? $this->data->post_excerpt : null,
			'post_name' => isset($this->data->post_name) ? $this->data->post_name : apply_filters( 'editable_slug', $this->data->post_title ),
			'post_parent' => null,
			'post_status' => $this->data->post_status,
			'post_title' => $this->data->post_title,
			'post_type' => 'post',
		);
		// SET POST DATE IF SENT OTHERWISE WP WILL DEFAULT TO NOW
		if (isset($this->data->post_date_gmt)) {
			$data['post_date_gmt'] = $this->data->post_date_gmt;
		}
		if (isset($this->data->post_date)) {
			$data['post_date'] = $this->data->post_date;
		}	
		// See this URL for info on inserting a post and attachments:
		// https://wordpress.org/support/topic/adding-attachment-to-post-using-wp_insert_post#post-2268280
		// proper order is:
		// 1. wp_insert_post
		// 2. wp_insert_attachment
		// 3. wp_generate_attachment_metadata
		// 4. wp_update_attachment_metadata
		// 5. set_post_thumbnail
		$new_post_id = wp_insert_post( $data );
		// CHECK THAT POST WAS INSERTED OK
		if (!$new_post_id) {
			$this->error = self::ERROR_WORDPRESS;
			return false;
		}
		// KEEP TRACK OF THE MAX POST ID TO CACHEBUST OUR CSS AND ENSURE THAT BUBBLE COLOR IS CORRECT
		update_option('right_intel_max_post_id', $new_post_id);		
		// save images to disk (if any)
		$this->processImages( $new_post_id );
		// copy bubble image
		Ri_Upload::copy($this->data->bubble_img, 'bubble-blog.png');
		// HANDLE TAGS
		$this->data->id = $new_post_id;
		if (isset($this->data->tags) && count($this->data->tags)) {	// array of ids	
			$this->data->tags = array_map( 'intval', $this->data->tags ); // ids must be ints for wordpress
			wp_set_object_terms( $new_post_id, $this->data->tags, 'post_tag' );
		}
		if (isset($this->data->custom_tags) && count($this->data->custom_tags)) { // array of tag text
			wp_set_post_terms( $new_post_id, $this->data->custom_tags, 'post_tag', $append=true );
		}
		// ADD ri_post_id TO POSTMETA FOR REFERENCE (used to check if post has been pushed to this blog before)
		add_post_meta( $new_post_id, 'ri_post_id', $this->data->ri_post_id );
		// ADD auth info to postmeta for reference (e.g. allow manually styling bubbles differently for different connected instances)
		add_post_meta( $new_post_id, 'ri_instance_id', $rest->auth->id );
		// add yoast postmeta values if present (see endpoints/get_publish_options.php)
		$more_postmeta = array(
			'_yoast_wpseo_canonical',
			'_yoast_wpseo_meta-robots-noindex',
			'_yoast_wpseo_meta-robots-nofollow',
		);
		foreach ($more_postmeta as $meta_key) {
			if (isset($this->data->$meta_key) && strlen($this->data->$meta_key) > 0) {
				add_post_meta( $new_post_id, $meta_key, $this->data->$meta_key );
			}
		}
		return true;
	}
	
	public function processImages($new_post_id) {
		// copy post image and replace the <img> element's source
		if (preg_match('/<img\s.*?src="(.+?)"/is', $this->data->post_content, $match)) {
			list (, $url) = $match;
			$ext = pathinfo($url, PATHINFO_EXTENSION);
			/* EXAMPLE result of wp_upload_dir($post_date)
			$uploadDir = Array (
				[path] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads/2014/05”
				[url] => “http://rightintel/home/wp-content/uploads/2015/05”
				[subdir] => “/2014/05”
				[basedir] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads”
				[baseurl] => “http://rightintel/home/wp-content/uploads”
				[error] => false
			)
			 */
			$uploadDir = wp_upload_dir($this->data->post_date);
			$wp_path = $uploadDir['path'] . "/post-{$this->data->ri_post_id}.$ext";
			if (isset($this->data->existing_path) && $this->data->existing_path) {
				// file already fetched (will happen on upgrades)
				$image = file_get_contents($this->data->existing_path);
			}
			else {
				$image = Ri_Curl::getContents($url);
			}
			$wp_url_base = $uploadDir['url'];
			$wp_uploads_url = $uploadDir['baseurl'];
			file_put_contents($wp_path, $image);
			// insert image into media registry
			$wp_filetype = wp_check_filetype( $wp_path, null );
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => esc_attr( $this->data->post_title ),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			// create a post with post_type attachment
			$attachment_id = wp_insert_attachment( $attachment, $wp_path, $new_post_id );
			if (!$attachment_id) {
				trigger_error('Error inserting image as attachment', E_USER_WARNING);
				return false;
			}
			// generate info about the image dimensions and such
			// but we have to require the image processing functions in admin includes dir 
			// see https://wordpress.org/support/topic/undefined-function-wp_generate_attachment_metadata
			require_once ( ABSPATH . 'wp-admin/includes/image.php' );
			Ri_Thumbnails::registerRightIntelSize();
			$metadata = wp_generate_attachment_metadata( $attachment_id, $wp_path );
			if (!$metadata) {
				trigger_error('Error generating image attachment metadata', E_USER_WARNING);
				return false;
			}
			/* wp_generate_attachment_metadata will create a _wp_attachment_metadata record with serialized values like this:
			Array (
				[width] => 512
				[height] => 288
				[file] => 2014/10/post-3004.jpg
				[sizes] => Array (
					[thumbnail] => Array (...)
					[medium] => Array (...)
					[right-intel-post] => Array (
						[file] => post-3004-260x146.jpg
						[width] => 260
						[height] => 146
						[mime-type] => image/jpeg
					)
					[post-thumbnail] => Array (...)
				)
				[image_meta]  => Array (...)
			)
			*/
			// insert postmeta record with that metadata
			wp_update_attachment_metadata( $attachment_id, $metadata );
			// update post content to reflect shrunken image size
			if (isset($metadata['sizes']['right-intel-post']['file'])) {
				$this->_updateImageUrls($new_post_id, compact('url','wp_uploads_url','wp_url_base','metadata'));
			}
			// insert image as featured image
			// http://codex.wordpress.org/Function_Reference/set_post_thumbnail
			// http://wordpress.stackexchange.com/questions/26138/set-post-thumbnail-with-php
			set_post_thumbnail( $new_post_id, $attachment_id );
			if (@$this->data->existing_path) {
				// we are from an upgrade
				unlink($this->data->existing_path);
			}
			if (isset($metadata['sizes']['thumbnail']['file'])) {
				$wp_url_base . '/' . $metadata['sizes']['thumbnail']['file'];
			}
			return $attachment_id;
		}
		return false;
	}
	
	protected function _updateImageUrls($new_post_id, $image_info) {
		$new_content = $this->data->post_content;
		// replace link to full-size image
		$new_content = str_replace(
			'href="' . $image_info['url'] . '"', 
			'href="' . $image_info['wp_uploads_url'] . '/' . $image_info['metadata']['file'] . '"',
			$new_content
		);
		// replace image source
		$new_content = str_replace(
			'src="' . $image_info['url'] . '"', 
			'src="' . $image_info['wp_url_base'] . '/' . $image_info['metadata']['sizes']['right-intel-post']['file'] . '"',
			$new_content
		);
		wp_update_post( array(
			'ID' => $new_post_id,
			'post_content' => $new_content,
		) );
	}
	
	public function findByRiPostId() {
		global $wpdb;
		$sql = "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ri_post_id' AND meta_value = %s";
		$sql = $wpdb->prepare($sql, $this->data->ri_post_id);
		$row = $wpdb->get_row($sql);
		return $row ? $row->post_id : false;		
	}
	
	public function getPermalinkByRiPostId() {
		$wp_post_id = $this->findByRiPostId();
		if (!$wp_post_id) {
			return false;
		}
		// https://codex.wordpress.org/Function_Reference/get_permalink
		return get_permalink($wp_post_id);
	}
	
	public function getRiPostId() {
		if ($this->data->post_type != 'post') {
			return false;
		}
		$riPostId = get_metadata('post', $this->data->ID, 'ri_post_id');
		return is_array($riPostId) && isset($riPostId[0]) ? $riPostId[0] : false;
	}
	
	public function loadMetadata() {
		$meta = get_metadata( 'post', $this->data->ID );
		foreach ($this->metaFields as $name) {
			if (isset($meta[$name])) {
				$this->data->$name = $meta[$name][0];
			}
		}
	}
	
	public function getTemplatePath() {
		// look in theme's folders for single-right_intel.php or the like
		// then look in our plugin tpl/post.tpl.php
		// http://codex.wordpress.org/Function_Reference/get_theme_root
		$basePaths = array(get_theme_root(), __DIR__ . '/../../tpl');
		foreach ($basePaths as $base) {
			$tplPath = "$base/right-intel-post.php";
			if (is_file($tplPath)) {
				return $tplPath;
			}
		}
	}
	
	public function render($post_content) {
		$this->loadMetadata();
		$this->data->post_content_filtered = $post_content;
		ob_start();
		include($this->getTemplatePath());
		return ob_get_clean();
	}
	
	public function hasAnyRightIntelPosts() {
		global $wpdb;
		$sql = "SELECT EXISTS (SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'ri_post_id') as has_one";
		$row = $wpdb->get_row($sql);
		return (bool) @$row->has_one;
	}
	
}