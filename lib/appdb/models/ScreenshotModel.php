<?php
namespace appdb\models;

use appdb\sqlbeans\ScreenshotBean;
use hydrogen\config\Config;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;

class ScreenshotModel extends Model {
	protected static $modelID = 'sm';
	
	public function getByAppID__3600_screenshots($app_id) {
		$query = new Query('SELECT');
		$query->where('app_id = ?', $app_id);
		$query->orderby('id', 'ASC');
		return ScreenshotBean::select($query);
	}
	
	public function setScreenshots($app_id, $itunes_id, $shots) {
		$i = 0;
		$beans = array();
		foreach ($shots as $shot) {
			if ($i)
				$filename = "${itunes_id}_${i}screen.jpg";
			else
				$filename = "${itunes_id}screen.jpg";
			if ($this->saveScreenshotLocally($shot, $filename, $is_horiz)) {
				$bean = new ScreenshotBean();
				$bean->app_id = $app_id;
				$bean->shot_url = '%BASE_URL%/appimages/screenshots/' . $filename;
				$bean->is_horiz = $is_horiz ? 1 : 0;
				$beans[] = $bean;
				$i++;
			}
		}
		if (!$beans)
			return false;
		$query = new Query('DELETE');
		$query->from('screenshots');
		$query->where('app_id = ?', $app_id);
		$stmt = $query->prepare();
		$stmt->execute();
		foreach ($beans as $bean) {
			try {
				$bean->insert(true);
			}
			catch (QueryFailedException $e) {}
		}
		$this->cm->clear("sm_appid_$app_id");
		return true;
	}
	
	protected function saveScreenshotLocally($jpg_url, $filename, &$is_horiz=false) {
		if (!($img = @imagecreatefromjpeg($jpg_url)))
			return false;
		if (!(@imagejpeg($img, Config::getVal('paths', 'screenshot_path') . "/$filename", 95)))
			return false;
		$is_horiz = imagesx($img) > imagesy($img);
		@imagedestroy($img);
		return true;
	}
}

?>