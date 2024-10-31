<?php

/**
 * Handle API authentication between WordPress server and Sharpr server
 * It represents a wp_ri_credentials record which stores api keys for connected RI instances
 * This class has a property corresponding to each column in WordPress's wp_ri_credentials table
 */
class Ri_Credentials {
	
	/**
	 * wp_ri_credentials.id
	 * @var int
	 */
	public $id;
	
	/**
	 * wp_ri_credentials.api_login
	 * @var string  40-character randomly generated sha1 hash
	 */
	public $api_login;
	
	/**
	 * wp_ri_credentials.api_password
	 * @var string  40-character randomly generated sha1 hash
	 */
	public $api_password;
	
	/**
	 * wp_ri_credentials.wp_user_id
	 * The WordPress user id of the user who connected the blogs
	 * @var int
	 */
	public $wp_user_id;
	
	/**
	 * wp_ri_credentials.instance_name
	 * The name of the instance as seen in Sharpr
	 * @var string
	 */
	public $instance_name;
	
	/**
	 * wp_ri_credentials.created
	 * The date the instance was connected
	 * @var string
	 */
	public $created;
	
	/**
	 * Object representing a WordPress user with all its fields as in WordPress
	 * The user that connected the blog
	 * @var stdObject 
	 */
	public $User;
	
	/**
	 * The error code for the error that was thrown
	 * @var int
	 */
	public $error;
	
	/**
	 * API error when no credentials are passed
	 */
	const EMPTY_CREDENTIALS = 1;
	
	/**
	 * API Error for api_login not found
	 */
	const INVALID_LOGIN = 2;
	
	/**
	 * API Error for api_password not matching the given api_login
	 */
	const INVALID_PASSWORD = 3;
	
	/**
	 * API error for when the hash generated with the shared secret isn't valid
	 */
	const INVALID_CONNECTION = 4;
	
	/**
	 * Validate api_login and api_password
	 * @global type $wpdb  The WordPress database object
	 * @param string $login
	 * @param string $password
	 * @return boolean  True if credentials are valid
	 */
	public function validate($login, $password) {
		$this->api_login = $login;
		$this->api_password = $password;
		if (!$this->api_login || !$this->api_password) {
			$this->error = self::EMPTY_CREDENTIALS;
			return false;
		}
		global $wpdb;
		// check auth credentials in wp_ri_credentials table
		$sql = "SELECT cred.*, u.* 
			FROM {$wpdb->prefix}ri_credentials cred
			INNER JOIN {$wpdb->prefix}users u ON u.id = cred.wp_user_id
			WHERE cred.api_login = %s";
		$sql = $wpdb->prepare( $sql, $this->api_login );
		$credentials = $wpdb->get_row( $sql );
		if ( !$credentials ) {
			$this->error = self::INVALID_LOGIN;
			return false;
		}
		if ( $credentials->api_password != $this->api_password ) {
			$this->error = self::INVALID_PASSWORD;
			return false;
		}
		// everything is good so load values and user information into this object's attributes
		$this->load($credentials);
		return true;
	}
	
	/**
	 * Create a new random api_login and api_password
	 * @return \Ri_Credentials
	 */
	public function generateLogin() {
		$this->api_login = sha1(uniqid(true) . microtime(true));
		$this->api_password = sha1(uniqid(true) . microtime(true));
		return $this;
	}
	
	/**
	 * Load values from wp_ri_credentials and WordPress user object into this object's attributes
	 * @param stdClass $record  WordPress query result object from wp_ri_credentials table
	 * @return \Ri_Credentials
	 */
	public function load($record) {
		// iterate through all the columns in wp_ri_credentials
		foreach (array('id','api_login','api_password','wp_user_id','instance_name','created') as $field) {
			if (isset($record->$field)) {
				// copy to this object
				$this->$field = $record->$field;
			}
		}
		$this->User = new stdClass();
		if (isset($this->wp_user_id)) {
			$this->User->ID = $this->wp_user_id;
		}
		// iterate through all the fields that wordpress will give us
		foreach (array('user_login','user_nicename','user_email','user_url','user_registered','user_status','display_name') as $field) {
			if (isset($record->$field)) {
				$this->User->$field = $record->$field;
			}
		}
		return $this;
	}
	
	/**
	 * Save a new record in wp_ri_credentials
	 * @global type $wpdb  The WordPress database object
	 * @return int  The id of the new wp_ri_credentials table
	 */
	public function save() {
		global $wpdb;
		if ($this->id) {
			// right now we have no situation where we need to update credentials;
			// they must be deleted and readded
		}
		else {
			$wpdb->insert($wpdb->prefix . 'ri_credentials', array(
				'api_login' => $this->api_login, 
				'api_password' => $this->api_password, 
				'wp_user_id' => $this->wp_user_id, 
				'instance_name' => $this->instance_name, 
				'created' => $this->getCreatedDate()
			));
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Delete credentials when an instance is disconnected
	 * @global type $wpdb  The WordPress database object
	 * @return boolean  True on success
	 */
	public function delete() {
		global $wpdb;
		if ($this->id) {
			$sql = "DELETE FROM {$wpdb->prefix}ri_credentials WHERE id = %s";
			$sql = $wpdb->prepare( $sql, $this->id );
		}
		elseif ($this->api_login) {
			$sql = "DELETE FROM {$wpdb->prefix}ri_credentials WHERE api_login = %s";
			$sql = $wpdb->prepare( $sql, $this->api_login );
		}
		else {
			return false;
		}
		$result = $wpdb->query($sql);
		return $result;
	}
	
	/**
	 * Return the current date (use server's time zone)
	 * @return string
	 */
	public function getCreatedDate() {
		return date('Y-m-d H:i:s');
	}
	
	/**
	 * 
	 * @global type $wpdb
	 * @return \self
	 */
	public static function findAll() {
		global $wpdb;
		$sql = "SELECT cred.*, u.* 
			FROM {$wpdb->prefix}ri_credentials cred
			INNER JOIN {$wpdb->prefix}users u ON u.id = cred.wp_user_id
			ORDER BY cred.created";
		$rs = $wpdb->get_results( $sql );
		$creds = array();
		foreach($rs as $r) {
			$cred = new self();
			$cred->load($r);
			$creds[] = $cred;
		}
		return $creds;
	}
	
	public static function getConnectionUrl() {
		return RI_APP_URL . '/client_blogs/choose_instance';
	}
	
	public static function getConnectionFields() {
		$return_url = 
			(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https://" : "http://")
			. $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; 
		$return_url = preg_replace('/&connected=\d+&disconnected=\d+/', '', $return_url);
		$wp_user_id = wp_get_current_user()->ID;
		$sharedSecret = 'rBV8qvKDk40LkjHV03';
		$timestamp = time(); // TODO: is this GMT?
		$hash = sha1( $sharedSecret . $wp_user_id . $timestamp );
		$siteurl = get_option('siteurl');
		$home = get_option('home');
		return compact('return_url','siteurl','home','wp_user_id','timestamp','hash');
	}
	
	public function verifyConnection($wp_user_id, $timestamp, $hash) {
		$sharedSecret = 'P8oUM5kt79CckEqmQ1';
		$expectedHash = sha1( $sharedSecret . $wp_user_id . $timestamp );
		if ($expectedHash == $hash) {
			// TODO: work out timezone offset and check if timestamp is within 30 minutes
			return true;
		}
		$this->error = self::INVALID_CONNECTION;
	}
	
	public static function disconnect($api_login) {
		$json = Ri_Curl::getContents(RI_APP_URL . "/client_blogs/disconnect/$api_login");
		// send message that disconnect worked or didn't
		$result = json_decode($json);
		if ($result->success) {
			$cred = new Ri_Credentials();
			$cred->api_login = $api_login;
			$cred->delete();
			return true;
		}
		return false;
	}
	
	public static function disconnectAll() {
		$creds = self::findAll();
		foreach ($creds as $cred) {
			self::disconnect($cred->api_login);
		}
	}
	
}
