<?php
namespace appdb\usersession;

use hydrogen\config\Config;
use appdb\models\UserProfileModel;
use appdb\models\AutoLoginModel;
use appdb\models\PermissionSetModel;
use appdb\sqlbeans\PermissionSetBean;

class UserSession {
	protected static $userID = 0, $userBean = false, $pubps = false, $ipps = false, $opened=false;
	
	private function __construct() {}
	
	public static function autoLogin($pubkey, $useCache=true) {
		$alm = AutoLoginModel::getInstance();
		if ($useCache)
			$auto = $alm->getByPublicKeyCached($pubkey);
		else
			$auto = $alm->getByPublicKey($pubkey);
		if (!$auto) {
			static::$userID = 0;
			static::$userBean = false;
			return false;
		}
		$user = $auto->getMapped('user');
		$privkey = static::createPrivateKey($user);
		if ($privkey != $auto->privatekey) {
			$alm->delete($auto);
			static::$userID = 0;
			static::$userBean = false;
			return false;
		}
		static::$userID = $auto->user_id;
		static::$userBean = $user;
		$auto->set('last_used', 'NOW()', true);
		$auto->last_used_ip = $_SERVER['REMOTE_ADDR'];
		$alm->update($auto);
		return true;
	}
	
	public static function checkAuth($username, $password, $passHashed=false, $useCache=true) {
		if (!$passHashed)
			$password = md5($password);
		$upm = UserProfileModel::getInstance();
		if ($useCache)
			$profile = $upm->getByUsernameCached($username);
		else
			$profile = $upm->getByUsername($username);
		if (!$profile)
			return false;
		$checkpass = md5(md5($profile->salt) . $password);
		if ($checkpass != $profile->password)
			return false;
		return $profile;
	}
	
	protected static function createPrivateKey($user) {
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		return md5($user->id . ':' . $user->salt . ':' . $useragent);
	}
	
	protected static function createPublicKey() {
		return md5(session_id());
	}
	
	protected static function eatCookie($cookieName, $path=false, $domain=false) {
		$expiretime = time() - (60 * 60 * 24 * 365*2);
		return setcookie($cookieName, '', $expiretime, $path, $domain);
	}
	
	protected static function bakeCookie($cookieName, $value, $expiretime=false, $path=false, $domain=false) {
		if (!$expiretime)
			$expiretime = time() + 60 * 60 * 24 * 365*2; // Expire in two years by default
		if (!$domain) {
			$domain = $_SERVER['HTTP_HOST'];
			while (preg_match('/\\..+\\./', $domain))
				$domain = substr($domain, strpos($domain, '.') + 1);
		}
		if (!$path)
			$path = '/';
		return setcookie($cookieName, $value, $expiretime, $path, $domain);
	}
	
	public static function getPermission($perm) {
		$fields = PermissionSetBean::getFields();
		$pval = NULL;
		if (!in_array($perm, $fields))
			return false;
		if (static::$userBean)
			$pval = static::$userBean->getPermission($perm);
		else if (static::$pubps)
			return static::$pubps->$perm == 1;
		if (!is_null($pval)) {
			if (static::$ipps)
				return is_null(static::$ipps->$perm) ? $pval : static::$ipps->$perm;
			else
				return $pval;
		}
		return false;
	}
	
	public static function getUserBean() {
		return static::$userBean;
	}
	
	public static function getUserID() {
		return static::$userID;
	}
	
	public static function login($user, $pass, $rememberme, $verifyCode=false, $useCache=true) {
		$userBean = static::checkAuth($user, $pass, false, $useCache);
		if (!$userBean) {
			static::logout();
			return false;
		}
		if ($userBean->must_validate) {
			if (!$verifyCode)
				return false;
			$checkCode = md5($userBean->username . $userBean->email . $userBean->salt);
			if ($checkCode != $verifyCode)
				return false;
			$upm = UserProfileModel::getInstance();
			$upm->validate($userBean, 4);
		}
		if ($rememberme) {
			$privkey = static::createPrivateKey($userBean);
			$pubkey = static::createPublicKey();
			$alm = AutoLoginModel::getInstance();
			$alm->create($userBean->id, $pubkey, $privkey);
			static::bakeCookie('publickey', $pubkey);
		}
		$_SESSION['LOGGED_IN'] = true;
		$_SESSION['USER_ID'] = $userBean->id;
		static::$userID = $userBean->id;
		static::$userBean = $userBean;
		return true;
	}
	
	public static function logout() {
		$_SESSION['LOGGED_IN'] = false;
		$_SESSION['USER_ID'] = 0;
		static::$userID = 0;
		static::$userBean = false;
		static::eatCookie('publickey');
		$psm = PermissionSetModel::getInstance();
		static::$pubps = $psm->getByGroupNameCached('Public');
	}
	
	public static function open_session($useCache=true) {
		if (static::$opened)
			return false;
		session_start();
		$psm = PermissionSetModel::getInstance();
		if (isset($_SESSION['LOGGED_IN']) && $_SESSION['LOGGED_IN']
				&& isset($_SESSION['USER_ID']) && $_SESSION['USER_ID'] > 0) {
			$upm = UserProfileModel::getInstance();
			static::$userID = $_SESSION['USER_ID'];
			if ($useCache)
				static::$userBean = $upm->getByUserIDCached(static::$userID);
			else
				static::$userBean = $upm->getByUserID(static::$userID);
			if (!static::$userBean)
				static::$userID = 0;
		}
		else if (isset($_COOKIE['publickey']))
			static::autoLogin($_COOKIE['publickey'], $useCache);
		if (!static::$userBean) {
			if ($useCache)
				static::$pubps = $psm->getByGroupNameCached('Public');
			else
				static::$pubps = $psm->getByGroupName('Public');
		}
		else if (!static::$userBean->getPermission('ip_ban_immune')
				&& strlen($_SERVER['REMOTE_ADDR']) >= 7) {
			if ($useCache)
				static::$ipps = $psm->getByIPAddressCached($_SERVER['REMOTE_ADDR']);
			else
				static::$ipps = $psm->getByIPAddress($_SERVER['REMOTE_ADDR']);
		}
		if (Config::getVal('mint', 'enabled', false) && static::$userBean && (!isset($_COOKIE['appdb_username']) ||
				$_COOKIE['appdb_username'] != static::$userBean->username))
			static::bakeCookie('appdb_username', static::$userBean->username);
		static::$opened = true;
		return true;
	}
	
	public static function register($username, $password, $email, $requireValidation, &$validationCode) {
		$upm = UserProfileModel::getInstance();
		$salt = $upm->generateSalt();
		$group = $requireValidation ? 3 : 4;
		$result = $upm->create($username, $password, $email, $salt, $group, 1, false, $requireValidation);
		if ($result != UserProfileModel::CREATE_OK || !$requireValidation)
			return $result;
		$validationCode = md5($username . $email . $salt);
		return $result;
	}
}

?>