<?php

/**
 * Handle error messages passed back to the Sharpr server
 */
class Ri_ApiMessages {
	
	/**
	 * @var array Messages and their error codes
	 */
	public static $messages = array(
		'Ri_Credentials' => array(
			'EMPTY_CREDENTIALS' => array(1, 'This action requires authentication.'),
			'INVALID_LOGIN' => array(2, 'Invalid API Login or API Password.'),
			'INVALID_PASSWORD' => array(2, 'Invalid API Login or API Password.'),
			'INVALID_CONNECTION' => array(3, 'Connection hash invalid. Please check the shared secret.'),
		),
		'Ri_Post' => array(
			'ERROR_MISSING_FIELD' => array(10, 'Missing field.'),
			'ERROR_WORDPRESS' => array(11, 'WordPress error.'),
			'ERROR_DUPLICATE' => array(12, 'That post has already been submitted.')
		)
	);
	
	/**
	 * 
	 * @param object $object  An object with a property "error"
	 * @return array  An array where the first item is error code and the second is error message
	 */
	public static function getMessage($object) {		
		if (!@$object->error) {
			return array('errors'=>array('code'=>-2, 'message'=>"Unknown error with no code or message."));
		}
		$class = get_class($object);
		if (isset(self::$messages[$class])) {
			foreach (self::$messages[$class] as $name => $value) {
				if (constant("$class::$name") == $object->error) {
					list ($code, $message) = self::$messages[$class][$name];
					return array('errors'=>compact('code','message'));
				}
			}
		}
		return array('errors'=>array('code'=>-1, 'message'=>"Unknown error code `$object->error` in class `$class`."));
	}
	
}