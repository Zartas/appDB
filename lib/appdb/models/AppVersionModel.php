<?php
namespace appdb\models;

use appdb\sqlbeans\AppVersionBean;
use appdb\models\AppLinkModel;
use hydrogen\recache\RECacheManager;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\database\Query;
use hydrogen\model\Model;

class AppVersionModel extends Model {
	protected static $modelID = 'avm';
	
	const TOKEN_SIZE = 4;
	
	public function getByAppID__600_appdetails($app_id, $version=false, $limit=false) {
		$query = new Query('SELECT');
		$query->where('app_id = ?', $app_id);
		if ($version)
			$query->where('version = ?', $version);
		if ($limit !== false)
			$query->limit($limit);
		$query->orderby('intversion', 'DESC');
		return AppVersionBean::select($query);
	}
	
	public function deleteByAppID($app_id, $clearCache=true) {
		$alm = AppLinkModel::getInstance();
		if (!$alm->deleteByAppID($app_id, $clearCache))
			return false;
		$query = new Query('DELETE');
		$query->from('versions');
		$query->where('app_id = ?', $app_id);
		$stmt = $query->prepare();
		if (($success = $stmt->execute()) && $clearCache)
			$this->cm->clearGroup('appdetails');
		return $success;
	}
	
	public function getByITunesID__600_appdetails($itunes_id, $version=false) {
		$query = new Query('SELECT');
		$fields = AppVersionBean::getFields();
		foreach ($fields as $field)
			$query->field('v.' . $field);
		$query->from('applications', 'a');
		$query->join('versions', 'v', 'LEFT');
		$query->on('a.id = v.app_id');
		$query->where('a.itunes_id = ?', $itunes_id);
		if ($version)
			$query->where('v.version = ?', $version);
		$query->orderby('v.intversion', 'ASC');
		$stmt = $query->prepare();
		$stmt->execute();
		$vers = array();
		while ($ver = $stmt->fetchAssoc())
			$vers[] = new AppVersionBean(false, $ver);
		return $vers;
	}
	
	public function create($app_id, $version, $verinfo) {
		$ver = $this->getByAppID($app_id, $version);
		if ($ver)
			return false;
		
		$query = new Query('SELECT');
		$query->field('MAX(intversion)', 'maxint');
		$query->from('versions');
		$query->where('app_id = ?', $app_id);
		$stmt = $query->prepare();
		$stmt->execute();
		if (!($row = $stmt->fetchObj()))
			$max = false;
		else
			$max = $row->maxint;
			
		if ($max) {
			$curTokens = $this->getNumTokens($max);
			$intver = $this->getIntVersion($version, $curTokens);
			$newTokens = $this->getNumTokens($intver);
			if ($newTokens > $curTokens)
				$this->updateTokens($app_id, $newTokens);
		}
		else
			$intver = $this->getIntVersion($version);
			
		$ver = new AppVersionBean();
		$ver->app_id = $app_id;
		$ver->version = $version;
		$ver->intversion = $intver;
		$ver->versioninfo = $verinfo;
		$ver->set('date_added', 'NOW()', true);
		$ver->set('last_updated', 'NOW()', true);
		try {
			$ver->insert();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		return true;
	}
	
	protected function updateTokens($app_id, $numTokens) {
		$vers = $this->getByAppID($app_id);
		foreach ($vers as $ver) {
			$intver = $this->getIntVersion($ver->version, $numTokens);
			$ver->intversion = $intver;
			try {
				$ver->update();
			}
			catch (QueryFailedException $e) {}
		}
	}
	
	protected function getNumTokens($intversion) {
		$thisclass = get_class($this);
		$numtokens = (int)(strlen($intversion) / $thisclass::TOKEN_SIZE);
		if (strlen($intversion) % $thisclass::TOKEN_SIZE > 0)
			$numtokens++;
		return $numtokens;
	}
	
	protected function getIntVersion($ver, $numTokens=false) {
		$ver = preg_replace("/[^0-9]/", ':', $ver);
		$ver = preg_replace("/:+/", ':', $ver);
		$tokens = explode(':', $ver);
		$numstr = '';
		$first = true;
		$thisclass = get_class($this);
		foreach ($tokens as $token) {
			if ($first) {
				$numstr .= $token;
				$first = false;
			}
			else
				$numstr .= $token . $this->createChars('0', $thisclass::TOKEN_SIZE - strlen($token));
		}
		if ($numTokens && count($tokens) < $numTokens) {
			$add = ($numTokens - count($tokens)) * $thisclass::TOKEN_SIZE;
			$numstr .= $this->createChars('0', $add);
		}
		return (int)$numstr;
	}
	
	protected function createChars($char, $num) {
		$str = '';
		for ($i = 0; $i < $num; $i++)
			$str .= $char;
		return $str;
	}
}

?>