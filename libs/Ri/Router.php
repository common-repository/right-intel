<?php

/**
 * Convenience class for adding pages, endpoints, css, and js
 */
class Ri_Router {
	
	protected $_pageUrls = array();
	
	/**
	 * Expose a hidden page that does not require admin abilities
	 * api_login and api_password should be checked if needed
	 * @param string $file  The file to expose: file will be at ../../endpoints/$file and url will be $blog/right_intel/$file
	 */
	public function addEndpoint($file) {
		add_action('template_redirect', function() use($file) {
			global $wp;
			$path = __DIR__ . "/../../endpoints/$file";
			if ($wp->request == "right_intel/$file" && is_file($path)) {
				require_once($path);
			}				
		}, 0);		
	}
		
	/**
	 * In order to register our endpoints, permalinks must be enabled
	 * @return boolean  True if WordPress installation meets requirements
	 */	
	public static function validateInstall() {
		if (trim(get_option('permalink_structure')) == '') {
			Ri_Flash::add("<strong>Sharpr Plugin Notice:</strong> WordPress permalinks must be enabled. Go to <a href=\"options-permalink.php\">Settings &rsaquo; Permalinks</a> and choose something other than the default.", 'error');
			return false;
		}
		return true;
	}
		
	/**
	 * Define a front end / admin page
	 * @param array $info  Options that define the page
	 * Required keys
	 *   function => the wordpress function to use (supports add_submenu_page and add_options_page)
	 *   page_title => the text that shows in <head>
	 *   menu_title => the text that shows in the admin menu
	 * Optional keys
	 *   parent_slug => the wordpress page that is the parent. If null or missing, it will be a hidden page
	 *   capability => permission required. defaults to manage_options
	 *   menu_slug => the address that it is accessible from
	 * @return \Ri_Router
	 */
	public function addPage($info) {
		$defaults = array(
			'parent_slug' => 'options.php',
			'capability' => 'manage_options',
			'menu_slug' => strtolower(preg_replace('/\W+/', '_', $info['page_title'])),
		);
		foreach ($defaults as $opt => $default) {
			if (!isset($info[$opt])) {
				$info[$opt] = $default;
			}
		}
		$router = $this;
		add_action('admin_menu', function() use($info, $router) {
			$includer = function() use($info, $router) {
				$base = RI_BASE_DIR . '/pages';
				$toInclude = array(
					"$base/controllers/{$info['file']}.php",
					"$base/views/{$info['file']}.tpl.php",
				);
				foreach ($toInclude as $path) {
					if (is_file($path)) {
						include($path);
					}
				}
			};
			if ($info['function'] == 'add_submenu_page') {
				add_submenu_page(
					$info['parent_slug'], 
					$info['page_title'], 
					$info['menu_title'], 
					$info['capability'], 
					$info['menu_slug'],
					$includer
				);
			}
			elseif ($info['function'] == 'add_options_page') {
				add_options_page(
					$info['page_title'], 
					$info['menu_title'], 
					$info['capability'], 
					$info['menu_slug'],
					$includer	
				);
			}
		});
		$pageUrl = get_option('siteurl') . '/wp-admin/' . $info['parent_slug'] . '?page=' . $info['menu_slug'];
		$this->_pageUrls[$info['file']] = $pageUrl;
		return $pageUrl;
	}
	
	public function addShortcode($shortcode, $defaults = array()) {
		add_shortcode($shortcode, function($attr, $content = '') use($shortcode, $defaults) {
			$attr = shortcode_atts($defaults, $attr, $shortcode);
			$path = RI_BASE_DIR . "/shortcodes/$shortcode.php";
			if (is_file($path)) {
				include($path);
				return $exports($attr, $content);
			}
			return "Sharpr Plugin Error: No file found for shortcode $shortcode.";
		});
	}
	
	public function getPageUrl($fileSlug) {
		return @$this->_pageUrls[$fileSlug];
	}
	
	/**
	 * Add a link to Sharpr settings page on the plugin page
	 * @param string $name  The text to display ("Settings")
	 * @param string $target  The link href
	 * @return Ri_Router
	 */
	public function addPluginListLink($name, $target) {
		$plugin = plugin_basename(RI_BASE_PAGE); 
		add_filter("plugin_action_links_$plugin", function($links) use($name, $target) {
			$settings_link = '<a href="' . esc_attr($target) . '">' . esc_html($name) . '</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		});
		return $this;
	}
	
	/**
	 * Check version number on all admin pages and run upgrade scripts as needed
	 * @param string $whenBeforeVersion  The version number at which this upgrade script became necessary
	 * @param Callable $handler  The callback to call after admin_init; it should return truthy if the upgrade succeeded
	 * @return \Ri_Router
	 */
	public function addUpgradeHandler($whenBeforeVersion, $handler) {
		$storedVersion = get_option('right_intel_semver');
		if (version_compare($whenBeforeVersion, $storedVersion) > 0) {
			if (count($this->upgradeHandlers) === 0) {
				add_action('admin_init', array($this, 'runUpgradeHandlers'));
			}
			$this->upgradeHandlers[] = $handler;
		}
	}
	
	public function runUpgradeHandlers() {
		$successes = 0;
		$storedVersion = get_option('right_intel_semver');
		foreach ($this->upgradeHandlers as $handler) {
			$return = $handler($storedVersion);
			$successes += ($return === false ? 0 : 1);
		}
		if (count($this->upgradeHandlers) === $successes) {
			self::setVersion();		
		}
	}
	
	public static function setVersion() {
		update_option('right_intel_version', RI_VERSION);			
		update_option('right_intel_semver', RI_SEMVER);	
	}
	
	public function addAdminNotice($html, $cssClass = 'updated') {
		add_action( 'admin_notices', function() use($html, $cssClass) {
			?>
			<div class="ri-admin-notice <?php echo esc_html($cssClass)?>">
				<p><?php echo $html?></p>
			</div>
			<?php
		});
	}
	
}