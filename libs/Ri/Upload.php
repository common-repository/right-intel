<?php

class Ri_Upload {
	
	public static function install() {
		if (self::isBasePathWriteable()) {
			// initially copy our orange ri bubble
			self::copy(__DIR__ . '/../../img/bubble-blog.png', '/bubble-blog.png');
			self::putContents('bubble-blog.css', '/*empty until the first post is added*/');
		}
	}
	
	public static function validateInstall() {
		if (!self::isBasePathWriteable()) {
			$pathinfo = self::getBasePathInfo();
			if (is_array($pathinfo) && isset($pathinfo['path'])) {
				Ri_Flash::add("<strong>Error:</strong> The WordPress content uploads directory is not writeable.
					Sharpr needs the following path to be writeable: `{$pathinfo['path']}`.", 'error');
			} 
			elseif (is_array($pathinfo) && isset($pathinfo['error'])) {
				Ri_Flash::add("<strong>Error:</strong> The WordPress reported the following error: `{$pathinfo['error']}`.", 'error');
			}
			else {
				Ri_Flash::add("<strong>Error:</strong> Error requesting the location of the uploads directory using `wp_upload_dir('ri');` 
					The WordPress content uploads directory may be missing or read only.
					Please check your WordPress installation.", 'error');
			}
			return false;
		}
		return true;
	}
	
	public static function getCssOverrideUrl() {
		$pathinfo = self::getBasePathInfo();
		if (isset($pathinfo['url'])) {
			$baseurl = rtrim($pathinfo['url'], '/');
			return "$baseurl/bubble-blog.css";
		}
		return false;
	}
	
	public static function writeCssOverride($bubble_color) {
		$css = self::getCssOverride($bubble_color);
		$bytes = self::putContents('bubble-blog.css', $css);
		return $bytes;
	}
	
	public static function getCssOverride($bubble_color = '#F26522') {
		// ensure we have the correct bubble images and colors
		$css = "
		.ri-bubble {
			background-color: %s
		}
		.ri-bubble:before,
		.ri-bubble:after {
			background-image: url('%s/bubble-blog.png?v%s')
		}
		";
		$css = preg_replace('/\s+/', '', $css);
		$pathinfo = self::getBasePathInfo();
		// get url to writeable without the domain so we can switch domains ok
		$imgpath = preg_replace('~^https?://.+?/~', '/', $pathinfo['url']);
		$imgpath = rtrim($imgpath, '/');
		$maxPostId = get_option('right_intel_max_post_id', 0);
		$css = sprintf($css, $bubble_color, $imgpath, $maxPostId);
		return $css;
	}
	
	public static function getBasePathInfo() {
		/* EXAMPLE DATA
		Array (
		  [path] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads/ri/”
		  [url] => “http://rightintel/home/wp-content/uploads/ri/”
		  [subdir] => “/ri/”
		  [basedir] => “/Users/ksnyder/Sites/rightintel/Wordpress/wp-content/uploads”
		  [baseurl] => “http://rightintel/home/wp-content/uploads”
		  [error] => false
		)
		 */
		return wp_upload_dir('ri');
	}
	
	public static function getBasePath() {
		$pathinfo = self::getBasePathInfo();
		return rtrim($pathinfo['path'], '/');
	}
	
	public static function exists($filename) {
		$basepath = self::getBasePath();
		return is_file("$basepath/$filename");
	}
	
	public static function isBasePathWriteable() {
		$basedir = self::getBasePath();
		if (!is_dir($basedir)) {
			@mkdir($basedir, 0777);
		}
		return is_writeable($basedir);
	}
	
	public static function copy($source, $toFilename) {
		$source = trim($source);
		$toFilename = trim($toFilename);
		if (preg_match('~^https?://~i', $source) && ini_get('allow_url_fopen') == false) {
//			trigger_error("`allow_url_fopen` is disabled; using curl...", E_USER_WARNING);	
			$data = Ri_Curl::getContents($source);
			return self::putContents($toFilename, $data);
		}
		$basepath = self::getBasePath();
		$ok = copy($source, "$basepath/$toFilename");
		@chmod("$basepath/$toFilename", 0777);
		return $ok;
	}
	
	public static function rename($oldName, $newName) {
		$basepath = self::getBasePath();
		$ok = rename("$basepath/$oldName", "$basepath/$newName");
		@chmod("$basepath/$newName", 0777);
		return $ok;
	}
	
	public static function getContents($filename) {
		$basepath = self::getBasePath();
		return file_get_contents("$basepath/$filename");
	}
	
	public static function putContents($filename, $string) {
		$basepath = self::getBasePath();
		$bytes = file_put_contents("$basepath/$filename", $string);
		if ($bytes === false) {
			trigger_error("failure - Ri_Upload::putContents('$filename',\$string)", E_USER_WARNING);	
		}
		if ($bytes === 0) {
			trigger_error("Zero bytes written - Ri_Upload::putContents('$filename',\$string)", E_USER_WARNING);	
		}
		@chmod("$basepath/$filename", 0777);
		return $bytes;
	}
	
	public static function url($filename) {
		$pathinfo = self::getBasePathInfo();
		return rtrim($pathinfo['url'], '/') . '/' . $filename;
	}
	
}
