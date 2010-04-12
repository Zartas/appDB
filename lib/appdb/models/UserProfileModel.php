<?php
namespace appdb\models;

use appdb\sqlbeans\UserProfileBean;
use appdb\sqlbeans\AutoLoginBean;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;

class UserProfileModel extends Model {
	protected static $modelID = 'upm';
	
	const CREATE_OK = 1;
	const CREATE_USERNAME_EXISTS = 0;
	const CREATE_EMAIL_EXISTS = -1;
	const CREATE_ERROR = -2;
	
	public function getByUsername__300_users($username) {
		$query = new Query('SELECT');
		$query->where('usr.username like ?', $username);
		$result = UserProfileBean::select($query, true);
		if (count($result) == 0)
			return false;
		return $result[0];
	}
	
	public function getByUserID__3600_users($user_id) {
		$query = new Query('SELECT');
		$query->where('usr.id like ?', $user_id);
		$result = UserProfileBean::select($query, true);
		if (count($result) == 0)
			return false;
		return $result[0];
	}
	
	public function forceDeleteByUsername($username) {
		$query = new Query('DELETE');
		$query->from('users');
		$query->where('username like ?', $username);
		$query->limit(1);
		$stmt = $query->prepare();
		return $stmt->execute();
	}
	
	public function updateUser($userBean) {
		$namechange = $userBean->fieldChanged('username');
		try {
			$userBean->update();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		if ($namechange)
			$this->cm->clearGroup('users');
		else
			$this->cm->clear('upm_uname_' . $userBean->username);
	}
	
	public function create($username, $password, $email, $salt=false, $group_id=3,
			$psoverride=1, $ipaddr=false, $mustValidate=true) {
		$user = $this->getByUsername($username);
		if ($user)
			return self::CREATE_USERNAME_EXISTS;
		$query = new Query('SELECT');
		$query->field('COUNT(*)', 'count');
		$query->from('users');
		$query->where('email like ?', $email);
		$stmt = $query->prepare();
		$stmt->execute();
		$obj = $stmt->fetchObj();
		if ($obj->count > 0)
			return self::CREATE_EMAIL_EXISTS;
		$salt = $salt ?: $this->generateSalt();
		$passhash = md5(md5($salt) . md5($password));
		$bean = new UserProfileBean();
		$bean->username = $username;
		$bean->email = $email;
		$bean->group_id = $group_id;
		$bean->permission_override_id = $psoverride;
		$bean->password = $passhash;
		$bean->salt = $salt;
		$bean->set('joindate', "NOW()", true);
		$bean->reg_ip = $ipaddr ?: $_SERVER['REMOTE_ADDR'];
		$bean->must_validate = $mustValidate ? 1 : 0;
		try {
			$bean->insert();
		}
		catch (QueryFailedException $e) {
			return self::CREATE_ERROR;
		}
		return self::CREATE_OK;
	}
	
	public function validate(&$userBean, $toGroup=false) {
		$userBean->must_validate = 0;
		if ($toGroup !== false)
			$userBean->group_id = $toGroup;
		try {
			$userBean->update(false, true);
		}
		catch (QueryFailedException $e) {
			return false;
		}
		return true;
	}
	
	public function generateSalt($numChars=5) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-=_+[]{}\\|;:'\",./<>?~`";
		$salt = '';
		for ($i = 0; $i < $numChars; $i++)
			$salt .= $chars[rand(0, strlen($chars) - 1)];
		return $salt;
	}
}

?>