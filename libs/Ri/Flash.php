<?php

if (isset($_REQUEST['ri_flash_msg'])) {
	Ri_Flash::add( stripslashes_deep($_REQUEST['ri_flash_msg']), isset($_REQUEST['ri_flash_class']) ? stripslashes_deep($_REQUEST['ri_flash_class']) : 'updated' );
}

if (isset($_COOKIE['ri_flashes']) && is_string($_COOKIE['ri_flashes'])) {
	$_COOKIE['ri_flashes'] = @unserialize(stripslashes_deep($_COOKIE['ri_flashes'])) ?: array();
}

class Ri_Flash {
	
	public static function add($msg, $class = 'updated') {
		if (!isset($_COOKIE['ri_flashes'])) {
			$_COOKIE['ri_flashes'] = array();
		}		
		$_COOKIE['ri_flashes'][$msg] = $class;
		self::write();
	}
	
	public static function output() {
		if (isset($_COOKIE['ri_flashes']) && count($_COOKIE['ri_flashes'])) {
			echo '<div id="RiFlashes">';
			foreach ($_COOKIE['ri_flashes'] as $html => $class) {
				echo "<div class=\"ri-flash $class\"><p>$html</p></div>\n";
			}
			echo '</div>';
			$_COOKIE['ri_flashes'] = array();
			setcookie('ri_flashes', '', 0, '/');
		}
	}
	
	public function write() {
		$data = serialize($_COOKIE['ri_flashes']);
		setcookie('ri_flashes', $data, 0, '/');
	}
	
}