<?php

class Ri_Styling {
	
	public static $fields = array('color_text','color_bubble','image_float','use_oswald','bubble_type','image_display_type');
	
	public static function getDefaults() {
		$currentBubbleColor = static::_getCurrentBubbleColor();
		return array(
			'color_text' => '#ffffff',
			'color_bubble' => $currentBubbleColor,
			'image_float' => 'left',
			'image_display_type' => 'both',
			'use_oswald' => '1',
			'bubble_type' => 'image',
		);
	}
	
	public static function setDefaults() {
		$defaults = static::getDefaults();
		// When installing or upgrading, we need styling option defaults
		static::update($defaults);
	}
	
	public static function hasOption($field) {
		$value = get_option("right_intel_styling_$field");
		return !empty($value);
	}
	
	public static function getStyleValues($overrides = array()) {
		$data = array();
		$defaults = static::getDefaults();
		foreach (self::$fields as $field) {
			$setValue = get_option("right_intel_styling_$field");
			$data[$field] = isset($overrides[$field]) ? trim($overrides[$field]) : (strval($setValue) === '' ? $defaults[$field] : $setValue);
		}
		$data['cachebust'] = isset($overrides['cachebust']) ? $overrides['cachebust'] : (get_option('right_intel_styling_last_modified') ?: $defaults[$field]);
		return $data;
	}
	
	public static function save($values) {
		$numUpdates = 0;
		foreach (self::$fields as $field) {
			if (isset($values[$field])) {
				$numUpdates += (int) update_option("right_intel_styling_$field", $values[$field]);
			}
		}
		return $numUpdates;
	}
	
	public static function _getCurrentBubbleColor() {
		$dir = wp_upload_dir('ri');
		$cssPath = rtrim($dir['path'], '/') . '/bubble-blog.css';
		if (!file_exists($cssPath)) {
			return '#f26522';
		}
		$css = file_get_contents($cssPath);
		if (!preg_match('/#[0-9A-F]{6}/i', $css, $match)) {
			return '#f26522';
		}
		// One or more instances have been connected and used
		update_option('right_intel_has_connected_before', '1');
		return $match[0];
	}
	
	public static function update($values) {
		$hasChanged = false;
		foreach ($values as $name => $value) {
			$current = get_option("right_intel_styling_$name");
			if (strpos($name, 'color') !== false) {
				$value = strtolower($value);
				$current = strtolower($current);
			}
			if ($current !== $value) {
				update_option("right_intel_styling_$name", $value);
				$hasChanged = true;
			}
		}
		if ($hasChanged) {
			update_option('right_intel_styling_last_modified', time());
		}
	}
	
	public function setupCss() {
		$this->addCss( 'admin_enqueue_scripts', 'admin.css' );
		$this->addCss( 'init', static::getCssRelativeUrl(), RI_VERSION . '-' . get_option('right_intel_max_post_id') );
		$this->addEditorCss( static::getCssRelativeUrl() );
	}
	
	public static function getCssRelativeUrl($overrides = array()) {
		return 'dynamic.css.php?' . static::getCssUrlQuery($overrides);
	}
	
	public static function getCssUrlQuery($overrides = array()) {
		$opt = static::getStyleValues($overrides);
		return http_build_query($opt, null, '&');
	}
	
	public function setupJs() {
		$this->addJs( 'admin_enqueue_scripts', 'admin.js' );
	}
	
	public function setupBodyClass() {
		add_filter( 'body_class', function( $classArray ) {
			global $post;
			if ( ! $post || ! $post->ID ) {
				return $classArray;
			}
			$ri_post_id = get_post_meta( $post->ID, 'ri_post_id', $asString=true );
			$ri_instance_id = get_post_meta( $post->ID, 'ri_instance_id', $asString=true );
			if ( $ri_post_id ) {
				$classArray[] = 'right-intel-post';
				$classArray[] = 'right-intel-id-' . $ri_post_id;
			}
			if ( $ri_instance_id ) {
				$classArray[] = 'right-intel-instance-' . $ri_instance_id;
			}
			return $classArray;
		} );	
		return $this;
	}
	
	public function setupPostThumbnails() {
		// hide post image dispaly if set to do so
		if ( get_option( 'right_intel_styling_image_display_type' ) == 'post_only' ) {
			add_filter( 'post_thumbnail_html', function($html) {
				global $post; // the post in the loop or on a detail page
				if ( ! $post || ! $post->ID ) {
					// somehow we don't know which post we are on
					return $html;
				}
				$ri_post_id = get_post_meta( $post->ID, 'ri_post_id', $asString=true );
				if ( $ri_post_id ) {	
					// A Sharpr Post
					return ''; 				
				}
				// A non-Sharpr post
				return $html;
			});
		}				
	}
	
	/**
	 * Add a css file to be included on pages or admin pages
	 * @param string $onAction  the hook to which to attach via add_action
	 * @param string $file      the name of the css file
	 * @return \Ri_Router
	 */
	public function addCss($onAction, $file, $version = false) {
		$url = plugins_url("/css/$file", RI_BASE_PAGE);
		$name = md5($file);
		add_action($onAction, function() use($name, $url, $version) {
			wp_enqueue_style( $name, $url, array(), $version, 'all' );
		});
		return $this;
	}
	
	/**
	 * Add a css file to the admin text editor
	 * @param string $file  the name of the css file
	 * @return \Ri_Router
	 */
	public function addEditorCss($file) {
		$url = plugins_url("/css/$file", RI_BASE_PAGE);
		// TODO: attach to other editor types
		add_filter('mce_css', function($mce_css) use($url) {
			if ( ! empty( $mce_css ) ) {
				$mce_css .= ',';
			}
			$mce_css .= $url;
			return $mce_css;
		});
		return $this;
	}
	
	/**
	 * Add a js file to regular or admin pages
	 * @param string $onAction  the hook to which to attach via add_action
	 * @param string $file      the name of the css file
	 * @return \Ri_Router
	 */
	public function addJs($onAction, $file) {
		$url = $this->getPluginsUrl('js', $file);
		$name = md5($file);
		add_action($onAction, function() use($name, $url) {
			wp_enqueue_script( $name, $url, array('jquery'), RI_VERSION ); 
		});
		return $this;
	}
	
	/**
	 * Get the full url to an asset
	 * @param string $prefix  "css" or "js" depending on the folder of the asset
	 * @param string $file  The asset filename
	 * @return string
	 */
	public function getPluginsUrl($prefix, $file) {
		if (preg_match('~^https?://~', $file)) {
			return $file;
		}
		return plugins_url("/$prefix/$file", RI_BASE_PAGE);
	}
	
}