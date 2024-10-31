<?php

class Ri_Schema {
	
	public static function install() {
		self::create_table_ri_credentials();
		Ri_Router::setVersion();
		Ri_Flash::add('The Sharpr plugin has been successfully installed. To connect this blog to Sharpr, go to <a href="options-general.php?page=sharpr_settings">Settings &rsaquo; Sharpr</a>.');
	}
	
	public static function uninstall() {
		// empty the credentials table and let sharpr.com know
		Ri_Credentials::disconnectAll();
		// drop the tables
		self::drop_table_ri_credentials();
	}
	
	public static function create_table_ri_credentials() {
		global $wpdb;

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ri_credentials (
			id INT(11) NOT NULL AUTO_INCREMENT,
			api_login CHAR(40) NOT NULL,
			api_password CHAR(40) NOT NULL,
			wp_user_id INT(11) NOT NULL,
			instance_name VARCHAR(50) NOT NULL,
			created DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY (api_login),
			KEY (wp_user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	public static function drop_table_ri_credentials() {
		global $wpdb;

		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ri_credentials");
	}	

	public static function drop_table_ri_widgets() {
		global $wpdb;

		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ri_widgets");
	}
	
}