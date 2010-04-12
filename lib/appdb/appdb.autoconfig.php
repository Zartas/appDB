<?php
namespace appdb;

use \appdb\api\KeyFactory;

/* 
 * The absolute path to any AppDB\KeyFactory compatible file.  This can be an
 * APIKey class (like the apikey_sample.pem.php included with Hydrogen\Config),
 * a straight PEM file (with proper openssl-generated PEM formatting) or a PHP
 * file that simply prints a properly formatted PEM key when executed.
 *
 * Note that an absolute path isn't required, but relative paths or use of the
 * realpath() function generate resource-expensive stat() calls.  Therefore,
 * absolute paths are much more desirable.
 */
KeyFactory::setKeyFile(__DIR__ . '/../../config/apikey.pem.php');

?>