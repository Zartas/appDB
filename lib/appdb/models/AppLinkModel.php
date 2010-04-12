<?php
namespace appdb\models;

use Exception;
use hydrogen\config\Config;
use hydrogen\database\Query;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;
use appdb\sqlbeans\AppLinkBean;
use appdb\itunes\AppStoreScraper;
use appdb\itunes\exceptions\AppNotFoundException;
use appdb\itunes\exceptions\TimeoutException;
use appdb\models\ApplicationModel;
use appdb\models\AppVersionModel;

class AppLinkModel extends Model {
	protected static $modelID = 'alm';
	
	const SUBMIT_OK										= 1;
	const SUBMIT_PARTIAL_OK								= 2;
	const SUBMIT_FAIL_APP_NOT_FOUND						= 0;
	const SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_APP		= -1;
	const SUBMIT_FAIL_USER_CANNOT_SUBMIT_FREE_APP		= -2;
	const SUBMIT_FAIL_USER_CANNOT_SUBMIT_LINKS			= -3;
	const SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_VERSION	= -4;
	const SUBMIT_FAIL_ITUNES_TIMEOUT					= -5;
	const SUBMIT_FAIL_NO_VALID_LINKS					= -6;
	const SUBMIT_FAIL_UNKNOWN_ERROR						= -7;
	
	public function getByAppID__300_applinks($app_id, $activeonly=true) {
		$query = new Query('SELECT');
		$query->where('lnk.app_id = ?', $app_id);
		if ($activeonly)
			$query->where('lnk.active = ?', 1);
		$query->orderby('lnk_ver.intversion', 'DESC');
		$query->orderBy('lnk.date_added', 'DESC');
		return AppLinkBean::select($query, array(
			'version' => false,
			'submitter' => false
			));
	}
	
	public function deleteByAppID($app_id, $clearCache=true) {
		$query = new Query('DELETE');
		$query->from('links');
		$query->where('app_id = ?', $app_id);
		$stmt = $query->prepare();
		if (($success = $stmt->execute()) && $clearCache)
			$this->cm->clearGroup('applinks');
		return $success;
	}
	
	public function countLinksBySubmitterID($sid) {
		$query = new Query('SELECT');
		$query->field('COUNT(*)', 'count');
		$query->from('links');
		$query->where('submitter_id = ?', $sid);
		$stmt = $query->prepare();
		$stmt->execute();
		$obj = $stmt->fetchObj();
		return $obj->count;
	}
	
	public function getUniqueURLs($linkArray) {
		if (!is_array($linkArray) || !$linkArray)
			throw new Exception('AppLinkModel::getUniqueURLs argument must be an array.');
		$query = new Query('SELECT');
		$query->field('url');
		$query->from('links');
		foreach ($linkArray as $link)
			$query->where('url like ?', '%' . $link . '%', 'OR');
		$stmt = $query->prepare();
		$stmt->execute();
		$existing = array();
		while ($row = $stmt->fetchObj())
			$existing[] = $row->url;
		$unique = array();
		foreach ($linkArray as $link) {
			$exists = false;
			foreach ($existing as $elink) {
				if (strtolower($link) == strtolower($elink) || in_array($link, $unique)) {
					$exists = true;
					break;
				}
			}
			if (!$exists)
				$unique[] = $link;
		}
		return $unique;
	}
	
	public function submit($itunes_id, $version, $cracker, $links, $userBean, 
			$submittedFrom='Web', $ignoreUserPermissions=false) {
		if (!$userBean->getPermission('submit_links_existing_apps') && !$ignoreUserPermissions)
			return self::SUBMIT_FAIL_USER_CANNOT_SUBMIT_LINKS;
		if (($version = trim($version)) == '')
			$version = 'unknown';
		try {
			$appinfo = new AppStoreScraper((int)$itunes_id);
		}
		catch (AppNotFoundException $e) {
			return self::SUBMIT_FAIL_APP_NOT_FOUND;
		}
		catch (TimeoutException $e) {
			return self::SUBMIT_FAIL_ITUNES_TIMEOUT;
		}
		$am = ApplicationModel::getInstance();
		$app = $am->getDetailsByITunesID($itunes_id, false);
		if (!$app) {
			if (!$userBean->getPermission('submit_new_itunes_apps') && !$ignoreUserPermissions)
				return self::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_APP;
			if (!$userBean->getPermission('submit_free_itunes_apps')
					&& strtolower($appinfo->getPrice()) == 'free' && !$ignoreUserPermissions)
				return self::SUBMIT_FAIL_USER_CANNOT_SUBMIT_FREE_APP;
			if (!$am->createFromITunesScraper($appinfo))
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			if (!($app = $am->getDetailsByITunesID($itunes_id, false)))
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			$newapp = true;
		}
		else
			$newapp = false;
		$avm = AppVersionModel::getInstance();
		$verBean = $avm->getByAppID($app->id, $version);
		if (!$verBean) {
			$testVersion = trim(preg_replace('/(?i)\(iP\w+ OS 3\S+ Tested\)/', '', $appinfo->getVersion()));
			if (!$userBean->getPermission('submit_unknown_app_versions') && !$ignoreUserPermissions
					&& $testVersion != $version) {
				return self::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_VERSION;
			}
			if (!$avm->create($app->id, $version, $appinfo->getVersionInfo()))
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			if (!($verBean = $avm->getByAppID($app->id, $version)))
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			$newver = true;
		}
		else
			$newver = false;
		$verBean = $verBean[0];
		$vers = $avm->getByAppID($app->id, false, 1);
		$updated = false;
		if (is_null($app->latest_version) || $app->latest_version == ''
				|| $verBean->id != $vers[0]->id || $app->latest_version != $verBean->version) {
			$app->latest_version = $version;
			$app->latest_version_first_cracker = $cracker;
			$app->set('latest_version_added', 'NOW()', true);
			try {
				$app->update();
			}
			catch (QueryFailedException $e) {
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			}
			$updated = true;
		}
		$submitLinks = array();
		$ulinks = $this->getUniqueURLs($links);
		foreach ($ulinks as $ulink) {
			if (substr(strtolower($ulink), 0, 7) != 'http://')
				$ulink = "http://" . $ulink;
			$parsed_url = @parse_url($ulink);
			if ($parsed_url) {
				$domain = strtolower($parsed_url['host']);
				while (preg_match('/\\..+\\./', $domain))
					$domain = substr($domain, strpos($domain, '.') + 1);
				$legaldomains = Config::getVal('domains', 'allowed', false);
				if (!preg_match('/[\<\>]/', $ulink) && (!$legaldomains || in_array($domain, $legaldomains)))
					$submitLinks[] = $ulink;
			}
		}
		if (count($submitLinks) == 0)
			return self::SUBMIT_FAIL_NO_VALID_LINKS;
		$bean = new AppLinkBean();
		$bean->app_id = $app->id;
		$bean->version_id = $verBean->id;
		$bean->filetype = 2;
		$bean->cracker = $cracker;
		$bean->set('date_added', 'NOW()', true);
		$bean->set('last_updated', 'NOW()', true);
		$bean->submitter_id = $userBean->id;
		$bean->submitter_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$bean->submitted_from = $submittedFrom;
		foreach ($submitLinks as $link) {
			$bean->url = $link;
			try {
				$bean->insert();
			}
			catch (QueryFailedException $e) {
				return self::SUBMIT_FAIL_UNKNOWN_ERROR;
			}
		}
		$this->cm->clear('alm_getByAppID_' . $app->id . '_1');
		$this->cm->clear('alm_getByAppID_' . $app->id . '_0');
		$this->cm->clear('alm_getByAppID_' . $app->id);
		if (!$newapp && ($updated || Config::getVal('general', 'update_app_every_submit') == '1'))
			$am->updateFromITunesScraper($app->id, $appinfo);
		if (count($submitLinks) != count($links))
			return self::SUBMIT_PARTIAL_OK;
		return self::SUBMIT_OK;
	}
}

?>
