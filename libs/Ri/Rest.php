<?php

Ri_Rest::$get = isset($_GET['ri']) ? stripslashes_deep($_GET['ri']) : array();
Ri_Rest::$post = isset($_POST['ri']) ? stripslashes_deep($_POST['ri']) : array();
Ri_Rest::$request = array_merge(Ri_Rest::$get, Ri_Rest::$post);

class Ri_Rest {
	
	public static $get;
	public static $post;
	public static $request;
	
	public $auth;
	public $errorLevel;
	public $phpErrors = array();
	
	public function __construct() {
		$this->errorLevel = error_reporting();
		set_error_handler(array($this,'_handleError'));
		set_exception_handler(array($this,'_handleException'));
	}
	
	public function _handleError($errno, $errstr, $errfile, $errline) {
		if (error_reporting() == 0 && $this->errorLevel != 0) {
			// error was supressed with @
			return;
		}
		if ($errno > 1024) {
			// ignore strict, deprecated, etc.
			return;
		}
		global $wp_version;
		$this->phpErrors[] = array(
			'php_version' => PHP_VERSION,
			'php_uname' => php_uname(),
			'system' => 'WordPress',
			'system_host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown',
			'system_ver' => isset($wp_version) ? $wp_version : 'unknown',
			'app' => 'Plugin',
			'app_ver' => defined('RI_VERSION') ? RI_VERSION : 'unknown',
			'app_semver' => defined('RI_SEMVER') ? RI_SEMVER : 'unknown',
			'errno' => $errno,
			'errstr' => $errstr,
			'errfile' => $errfile,
			'errline' => $errline,
			'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['HTTP_HOST'] : 'unknown',
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown',
		);
		return false;
	}
	
	public function _handleException($e) {
		$this->_handleError(-1, $e->getMessage(), $e->getFile(), $e->getLine());
	}
	
	public function sendErrors() {
		restore_error_handler();
		restore_exception_handler();
		if (count($this->phpErrors) > 0) {
			Ri_Curl::getContents(RI_APP_URL . '/log_remote_systems/add', array('entries'=>$this->phpErrors));
		}
		$this->phpErrors = array();
	}
	
	public static function run($callable) {
		$rest = new self();
		if (!$rest->authenticate()) {
			$rest->respond(array(
				'errors' => array($rest->auth)
			));
		}
		$response = call_user_func($callable, $rest);
		$rest->respond($response);
	}
	
	public function authenticate() {
		$this->auth = new Ri_Credentials();
		return $this->auth->validate(self::REQUEST('api_login'), self::REQUEST('api_password'));
	}
	
	public function respond($response) {
		$response['version'] = RI_VERSION;
		$response['semver'] = RI_SEMVER;
		foreach (array('data','errors','notices') as $prop) {
			if (!isset($response[$prop])) {
				$response[$prop] = array();
			}
		}
		if (!empty($this->phpErrors)) {
			$response['php_errors'] = $this->phpErrors;
		}
		// our output buffer was started at the top of right-intel.php
		if (ob_get_level() > 0 && ($ob = ob_get_clean())) {
			$response['server_errors'] = $ob;
		}
		header('HTTP/1.1 200 OK');
		header('Content-type: application/json; charset=utf-8');
		echo json_encode($response);		
		die();
	}
	
	public static function stripslashes($val) {
		return is_array($val) ? stripslashes_deep($val) : stripslashes($val); 		
	}
	
	public static function GET($name=null) {
		if ($name === null) {
			return self::$get;
		}		
		return isset(self::$get[$name]) ? self::$get[$name] : null;
	}
	
	public static function POST($name=null) {
		if ($name === null) {
			return self::$post;
		}		
		return isset(self::$post[$name]) ? self::$post[$name] : null;
	}
	
	public static function REQUEST($name=null) {
		if ($name === null) {
			return self::$request;
		}		
		return isset(self::$request[$name]) ? self::$request[$name] : null;
	}
	
}