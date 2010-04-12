<?php
namespace appdb\api;

use appdb\api\exceptions\KeyNotFoundException;
use appdb\api\exceptions\InvalidKeyException;

class KeyFactory {
	protected static $strkey = false, $opensslkey = false;
	
	private function __construct() {}
	
	public static function getKeyString() {
		return isset(static::$strkey) ? static::$strkey : false;
	}
	
	public static function getOpenSSLKey() {
		return isset(static::$opensslkey) ? static::$opensslkey : false;
	}
	
	public static function setKeyFile($path) {
		ob_start();
		$success = include($path);
		$content = ob_get_contents();
		ob_end_clean();
		if (!$success)
			throw new KeyNotFoundException("File '$path' does not exist or has errors.");
		if (class_exists('\appdb\api\APIKey'))
			static::setKey(\appdb\api\APIKey::KEY);
		else if (!isset(static::$opensslkey)) {
			if (($content = trim($content)) !== '')
				static::setKey($content);
			else
				throw new KeyNotFoundException("Key file appears to be empty.");
		}
	}
	
	public static function setKey($key, $passphrase='') {
		$pkey = openssl_pkey_get_private($key, $passphrase);
		if ($pkey === false)
			throw new InvalidKeyException('The key provided to the KeyFactory is not ' .
				'valid and cannot be parsed by OpenSSL.');
		static::$strkey = $key;
		static::$opensslkey = $pkey;
	}
	
}

?>