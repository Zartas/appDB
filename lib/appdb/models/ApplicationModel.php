<?php
namespace appdb\models;

use appdb\models\data\StopWords;
use appdb\models\AppCategoryModel;
use appdb\models\AppVersionModel;
use appdb\sqlbeans\ApplicationBean;
use appdb\sqlbeans\ShortAppBean;
use appdb\sqlbeans\NameIndexBean;
use appdb\sqlbeans\exceptions\QueryFailedException;
use hydrogen\config\Config;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\semaphore\SemaphoreEngineFactory;
use hydrogen\model\Model;

class ApplicationModel extends Model {
	protected static $modelID = 'am';
	
	public function getDetailsByAppID__3600_appdetails($appID, $doMapping=true) {
		$query = new Query('SELECT');
		$query->where('app.id = ?', $appID);
		return $this->getDetailsByQuery($query, $doMapping);
	}
	
	public function deleteByAppID($appID, $clearCache=true) {
		$avm = AppVersionModel::getInstance();
		if (!$avm->deleteByAppID($appID, $clearCache))
			return false;
		$query = new Query('DELETE');
		$query->from('applications');
		$query->where('id = ?', $appID);
		$stmt = $query->prepare();
		if (($success = $stmt->execute()) && $clearCache) {
			$this->cm->clearGroup('appdetails');
			$this->cm->clearGroup('applist');
		}
		return $success;
	}
	
	public function getDetailsByITunesID__3600_appdetails($iTunesID, $doMapping=true) {
		$query = new Query('SELECT');
		$query->where('app.itunes_id = ?', $iTunesID);
		return $this->getDetailsByQuery($query, $doMapping);
	}
	
	protected function getDetailsByQuery($query, $doMapping=true) {
		if (!$doMapping)
			$doMapping = array();
		$result = ApplicationBean::select($query, $doMapping);
		if (!$result)
			return false;
		else
			return $result[0];
	}
	
	public function getAppListCached($perpage, $page, $sort='relevance', $cat=0, $filter=false, 
			$app_ids=false, $itunes_ids=false, $overrideStartWith=false, $fullinfo=false) {
		$key = "appm_list_${perpage}";
		$key .= '_' . ($overrideStartWith ? '' : $page);
		$key .= "_${sort}_${cat}";
		$key .= '_' . ($filter ?: '');
		$key .= '_' . ($app_ids ? implode(':', $app_ids) : '');
		$key .= '_' . ($itunes_ids ? implode(':', $itunes_ids) : '');
		$key .= '_' . ($overrideStartWith ?: '0');
		$key .= '_' . ($fullinfo ? '1' : '0');
		if ($filter || $app_ids || $itunes_ids)
			$ttl = 300;
		else
			$ttl = 1800;
		return $this->cm->recache_get($key, $ttl, 'applist',
			array($this, 'getAppList'), array($perpage, $page, $sort, $cat, 
			$filter, $app_ids, $itunes_ids, $overrideStartWith, $fullinfo));
	}
	
	public function getAppList($perpage, $page, $sort='relevance', $cat=0, $filter=false, 
			$app_ids=false, $itunes_ids=false, $overrideStartWith=false, $fullinfo=false) {
		$query = new Query('SELECT');
		if (($filter = trim($filter)) == '')
			$filter = false;
		switch ($sort) {
			case 'appname':
				$query->orderby('app.name', 'ASC');
				break;
			case 'newapps':
				$query->orderby('app.date_added', 'DESC');
				break;
			case 'relevance':
				if ($filter) {
					$query->orderby('namematch', 'DESC');
					$query->orderby('score', 'DESC');
					break;
				}
			case 'newvers':
			default:
				$sort = 'newvers';
				$query->orderby('latest_version_added', 'DESC');
		}
		$query->limit($perpage, $overrideStartWith ?: ($perpage * ($page - 1)));
		$inclids = array();
		if ($filter) {
			$inclids = $this->getMatchedIDs($filter);
			$newfilter = $this->processFilter($filter);
			if ($sort == 'relevance') {
				$query->field('CASE when name like ? then 1 else 0 END', 'namematch', '%' . $filter . '%');
				$query->field('MATCH(app.name, app.description) AGAINST(? IN BOOLEAN MODE)' , 'score', $newfilter);
			}
			$query->whereOpenGroup('AND');
			$query->where('MATCH(app.name, app.description) AGAINST(? IN BOOLEAN MODE)' , $newfilter);
			foreach ($inclids as $inclid)
				$query->where('app.id = ?', $inclid, 'OR');
			$query->whereCloseGroup();
		}
		if ($cat > 0)
			$query->where('app.category_id = ?', $cat);
		if ($app_ids) {
			if (is_array($app_ids)) {
				$query->whereOpenGroup('AND');
				foreach ($app_ids as $app_id)
					$query->where('app.id = ?', $app_id, 'OR');
				$query->whereCloseGroup();
			}
			else
				$query->where('app.id = ?', $app_id);
		}
		if ($itunes_ids) {
			if (is_array($itunes_ids)) {
				$query->whereOpenGroup('AND');
				foreach ($itunes_ids as $itunes_id)
					$query->where('app.itunes_id = ?', $itunes_id, 'OR');
				$query->whereCloseGroup();
			}
			else
				$query->where('app.itunes_id = ?', $itunes_id);
		}
		if ($fullinfo)
			$result = ApplicationBean::select($query, true);
		else
			$result = ShortAppBean::select($query, true);
		if (!$result)
			return false;
		return $result;
	}
	
	public function getResultCountCached($cat=0, $filter=false, $app_ids=false, $itunes_ids=false) {
		$key = "appm_count_${cat}";
		$key .= '_' . ($filter ?: '');
		$key .= '_' . ($app_ids ? implode(':', $app_ids) : '');
		$key .= '_' . ($itunes_ids ? implode(':', $itunes_ids) : '');
		if ($filter || $app_ids || $itunes_ids)
			$ttl = 300;
		else
			$ttl = 1800;
		return $this->cm->recache_get($key, $ttl, 'applist',
			array($this, 'getResultCount'), array($cat, $filter, $app_ids, $itunes_ids));
	}
	
	public function getResultCount($cat=0, $filter=false, $app_ids=false, $itunes_ids=false) {
		$query = new Query('SELECT');
		$query->field('COUNT(*)', 'count');
		$query->from('applications');
		if ($cat > 0)
			$query->where('category_id = ?', $cat);
		if ($filter) {
			$inclids = $this->getMatchedIDs($filter);
			$newfilter = $this->processFilter($filter);
			$query->whereOpenGroup('AND');
			$query->where('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)' , $newfilter);
			foreach ($inclids as $inclid)
				$query->where('id = ?', $inclid, 'OR');
			$query->whereCloseGroup();
		}
		if ($app_ids) {
			if (is_array($app_ids)) {
				$query->whereOpenGroup('AND');
				foreach ($app_ids as $app_id)
					$query->where('id = ?', $app_id, 'OR');
				$query->whereCloseGroup();
			}
			else
				$query->where('id = ?', $app_id);
		}
		if ($itunes_ids) {
			if (is_array($itunes_ids)) {
				$query->whereOpenGroup('AND');
				foreach ($itunes_ids as $itunes_id)
					$query->where('itunes_id = ?', $itunes_id, 'OR');
				$query->whereCloseGroup();
			}
			else
				$query->where('tunes_id = ?', $itunes_id);
		}
		$stmt = $query->prepare();
		$stmt->execute();
		$obj = $stmt->fetchObj();
		return $obj->count;
	}
	
	public function getIndexable($string) {
		$stripped = trim(preg_replace("/[^a-zA-Z0-9'_]/", ' ', $string));
		$stripped = preg_replace('/(^|\s)[^a-zA-Z0-9](\s|$)/', ' ', $stripped);
		$words = explode(' ', strtolower($stripped));
		$iwords = array();
		foreach ($words as $word) {
			if (strlen($word) > 0 && isset(StopWords::$stopwords[$word]) && !in_array($word, $iwords))
				$iwords[] = $word;
		}
		return $iwords;
	}
	
	protected function getIndexMatchArray($indexableWordsArray) {
		$iwords = &$indexableWordsArray;
		if (count($iwords) == 0)
			return array();
		sort($iwords, SORT_STRING);
		$key = implode(',', $iwords);
		$inclids = $this->cm->get('inclids:' . $key);
		if ($inclids === false) {
			$inclids = array();
			$sindex = array();
			$getindex = array();
			$noexist = false;
			$shortest = false;
			$shortcount = false;
			foreach ($iwords as $iword) {
				$sindex[$iword] = $this->cm->get("search_name_index:$iword");
				if ($sindex[$iword] === false) {
					$getindex[] = $iword;
				}
				else if (count($sindex[$iword]) == 0) {
					$noexist = true;
					break;
				}
				else if ($shortest === false || count($sindex[$iword]) < $shortcount) {
					$shortest = $iword;
					$shortcount = count($sindex[$iword]);
				}
			}
			if (!$noexist) {
				if (count($getindex) > 0) {
					$query = new Query('SELECT');
					foreach ($getindex as $idx)
						$query->where('term = ?', $idx, 'OR');
					$results = NameIndexBean::select($query);
					$count = 0;
					foreach ($results as $row) {
						$sindex[$row->term] = unserialize($row->app_id_array);
						$this->cm->set("search_name_index:" . $row->term, $sindex[$row->term], 600, 'applist');
						if (count($sindex[$row->term]) == 0) {
							$noexist = true;
							break;
						}
						if ($shortest === false || count($sindex[$row->term]) < $shortcount) {
							$shortest = $row->term;
							$shortcount = count($sindex[$row->term]);
						}
						$count++;
					}
					if ($count != count($getindex))
						$noexist = true;
				}
			}
			if (!$noexist) {
				$inclids = $sindex[$shortest];
				foreach ($sindex as $term => $idarray) {
					if ($term != $shortest)
						$inclids = array_intersect($inclids, $idarray);
				}
			}
			$this->cm->set("inclids:$key", $inclids, 60);
		}
		return $inclids;
	}
	
	protected function getMatchedIDs($filter) {
		if (strpos($filter, '"') === false && !preg_match('/(^|\s)[\-~]\w+/', $filter))
			return $this->getIndexMatchArray($this->getIndexable($filter));
		return array();
	}
	
	protected function processFilter($filter) {
		// Require all words in the search
		$newfilter = '';
		$inquote = false;
		$addplus = true;
		for ($i = 0; $i < strlen($filter); $i++) {
			if ($filter{$i} == '"') {
				if ($inquote)
					$inquote = false;
				else {
					$inquote = true;
					if ($addplus) {
						$newfilter .= '+';
						$addplus = false;
					}
				}
			}
			if (!$inquote) {
				if ($filter{$i} == ' ' || $filter{$i} == '(')
					$addplus = true;
				else if ($addplus) {
					if (preg_match('/[+-~<>]/', $filter{$i}))
						$newfilter .= '+';
					$addplus = false;
				}
			}
			$newfilter .= $filter{$i};
		}
		return $newfilter;
	}
	
	public function updateFromITunesScraper($app_id, $appinfo) {
		$bean = $this->getDetailsByAppID($app_id, false);
		if (!$bean)
			return false;
		$bean->itunes_id = $appinfo->getITunesID();
		if (($set = $appinfo->getName()) && $set != $bean->name)
			$bean->name = $set;
		if (($set = $appinfo->getReleaseDate()) && $set != $bean->releasedate)
			$bean->releasedate = $set;
		if (($set = $appinfo->getSeller()) && $set != $bean->seller)
			$bean->seller = $set;
		if (($set = $appinfo->getCompany()) && $set != $bean->company)
			$bean->company = $set;
		if (($set = $appinfo->getSize()) && $set != $bean->size)
			$bean->size = $set;
		if (($set = $appinfo->getPrice()) && $set != $bean->price)
			$bean->price = $set;
		if (($set = $appinfo->getDescription()) && $set != $bean->description)
			$bean->description = $set;
		if (($set = $appinfo->getLanguages()) && $set != $bean->languages)
			$bean->languages = $set;
		if (($set = $appinfo->getRequirements()) && $set != $bean->requirements)
			$bean->requirements = $set;
		$smallicon = $appinfo->getITunesID() . 'icon-57x57.png';
		$bigicon = $appinfo->getITunesID() . 'icon-100x100.png';
		if (($set = $appinfo->getIconUrlPNG()) || $this->saveIconsLocally($set, $smallicon, $bigicon)) {
			$smallicon_url = '%BASE_URL%/appimages/icons/' . $smallicon;
			$bigicon_url = '%BASE_URL%/appimages/icons/' . $bigicon;
			if ($bean->smallicon_url != $smallicon_url)
				$bean->smallicon_url = $smallicon_url;
			if ($bean->bigicon_url != $bigicon_url)
				$bean->bigicon_url = $bigicon_url;
		}
		$bean->set('last_updated', 'NOW()', true);
		if ($set = $appinfo->getCategory()) {
			$acm = AppCategoryModel::getInstance();
			$cat = $acm->getByName($set);
			if (!$cat) {
				$acm->create($set);
				if (!($cat = $acm->getByName($set)))
					return false;
			}
			if ($bean->category_id != $cat->id)
				$bean->category_id = $cat->id;
		}
		try {
			$bean->update();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		$shots = $appinfo->getScreenshots();
		if ($shots) {
			$sm = ScreenshotModel::getInstance();
			$sm->setScreenshots($bean->id, $bean->itunes_id, $shots);
		}
		$this->indexName($bean->name, $bean->id);
		$this->cm->clearGroup('applist');
		$this->cm->clear('appm_appid_' . $bean->id);
		$this->cm->clear('appm_itunesid_' . $bean->itunes_id);
		return true;
	}
	
	public function createFromITunesScraper($appinfo, $iconsPreSaved=false) {
		$bean = new ApplicationBean();
		$bean->itunes_id = $appinfo->getITunesID();
		$bean->name = $appinfo->getName();
		$bean->releasedate = $appinfo->getReleaseDate();
		$bean->seller = $appinfo->getSeller();
		$bean->company = $appinfo->getCompany() ?: $bean->seller;
		$bean->size = $appinfo->getSize();
		$bean->price = $appinfo->getPrice();
		$bean->description = $appinfo->getDescription();
		$bean->languages = $appinfo->getLanguages();
		$bean->requirements = $appinfo->getRequirements();
		$smallicon = $appinfo->getITunesID() . 'icon-57x57.png';
		$bigicon = $appinfo->getITunesID() . 'icon-100x100.png';
		if ($iconsPreSaved || $this->saveIconsLocally($appinfo->getIconUrlPNG(), $smallicon, $bigicon)) {
			$bean->smallicon_url = '%BASE_URL%/appimages/icons/' . $smallicon;
			$bean->bigicon_url = '%BASE_URL%/appimages/icons/' . $bigicon;
		}
		$bean->set('date_added', 'NOW()', true);
		$bean->set('last_updated', 'NOW()', true);
		$catname = $appinfo->getCategory() ?: 'Unknown';
		$acm = AppCategoryModel::getInstance();
		$cat = $acm->getByName($catname);
		if (!$cat) {
			$acm->create($catname);
			if (!($cat = $acm->getByName($catname)))
				return false;
		}
		$bean->category_id = $cat->id;
		try {
			$bean->insert();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		$shots = $appinfo->getScreenshots();
		if ($shots) {
			if (!($bean = $this->getDetailsByITunesID($appinfo->getITunesID(), false)))
				return false;
			$sm = ScreenshotModel::getInstance();
			$sm->setScreenshots($bean->id, $bean->itunes_id, $shots);
		}
		$this->indexName($bean->name, $bean->id);
		$this->cm->clearGroup('applist');
		return true;
	}
	
	// TODO: Convert to V2
	protected function indexName($name, $id) {
		$iwords = $this->getIndexable($name);
		if (count($iwords) > 0) {
			$sem = SemaphoreEngineFactory::getEngine();
			$sindex = array();
			$create = array();
			$docreate = false;
			$query = new Query('SELECT');
			$query->field('term');
			$query->field('app_id_array');
			$query->from('search_name_index');
			foreach ($iwords as $iword) {
				$sindex[$iword] = array();
				$create[$iword] = true;
				$docreate = true;
				$query->where('term = ?', $iword, 'OR');
			}
			$stmt = $query->prepare();
			$semkey = Config::getVal('recache', 'unique_name') . ":LOCK:sindex";
			$sem->acquire($semkey);
			$stmt->execute();
			while ($row = $stmt->fetchObj()) {
				$create[$row->term] = false;
				$sindex[$row->term] = unserialize($row->app_id_array);
			}
			unset($query);
			unset($stmt);
			$iquery = new Query('INSERT');
			$iquery->ignore();
			$iquery->intoTable('search_name_index');
			$iquery->intoField('term');
			$iquery->intoField('app_id_array');
			$uqueries = array();
			foreach ($iwords as $iword) {
				if (!in_array($id, $sindex[$iword])) {
					$sindex[$iword][] = $id;
					$this->cm->set("search_name_index:$iword", $sindex[$iword], 0);
				}
				if ($create[$iword])
					$iquery->VALUES('(?, ?)', array($iword, serialize($sindex[$iword])));
				else {
					$query = new Query('UPDATE');
					$query->table('search_name_index');
					$query->set('app_id_array = ?', serialize($sindex[$iword]));
					$query->where('term = ?', $iword);
					$uqueries[] = $query;
				}
			}
			if ($docreate) {
				$stmt = $iquery->prepare();
				unset($iquery);
				$stmt->execute();
			}
			foreach ($uqueries as $uquery) {
				$stmt = $uquery->prepare();
				$stmt->execute();
			}
			$sem->release($semkey);
		}
	}
	
	public function saveIconsLocally($remotePNG, $smallfile, $bigfile) {
		if (!($original = @imagecreatefrompng($remotePNG)))
		 	return false;
		imagesavealpha($original, true);
		if (!($resized = @imagecreatetruecolor(57, 57)))
			return false;
		imagealphablending($resized, false);
		imagesavealpha($resized, true);
		$alphacolor = imagecolorallocatealpha($resized, 255, 255, 255, 127);
		imagefilledrectangle($resized, 0, 0, 57, 57, $alphacolor);
		imagealphablending($resized, true);
		if (!@imagecopyresampled($resized, $original, 0, 0, 0, 0, 57, 57, 100, 100))
			return false;
		imagealphablending($resized, false);

		if (!@imagepng($original, Config::getVal('paths', 'icon_path') . "/$bigfile"))
			return false;
		if (!@imagepng($resized, Config::getVal('paths', 'icon_path') . "/$smallfile"))
			return false;
		@imagedestroy($original);
		@imagedestroy($resized);
		return true;
	}
}

?>