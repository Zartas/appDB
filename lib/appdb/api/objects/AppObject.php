<?php
namespace appdb\api\objects;

use appdb\api\APIObject;
use appdb\models\ApplicationModel;
use appdb\sqlbeans\ShortAppBean;
use appdb\sqlbeans\ApplicationBean;
use appdb\api\ResponseCode;

class AppObject extends APIObject {
	
	public static function action_getDetails($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if ($auth) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		if (!$profile->perm_app_getdetails) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		if ((isset($args->app_id) && isset($args->itunes_id))
				|| (!isset($args->app_id) && !isset($args->itunes_id))) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		$am = ApplicationModel::getInstance();
		$result = false;
		if (isset($args->app_id))
			$result = $am->getDetailsByAppIDCached($args->app_id);
		else if (isset($args->itunes_id))
			$result = $am->getDetailsByITunesIDCached($args->itunes_id);
		if (!$result) {
			$returncode = ResponseCode::RESOURCE_NOT_FOUND;
			return false;
		}
		$fields = isset($args->fields) && is_array($args->fields) ? $args->fields : array();
		$legal = $profile->allowed_app_fields;
		$realfields = ApplicationBean::getFields();
		$app = array('id' => $result->id);
		foreach ($fields as $field) {
			if ($field != 'id' && in_array($field, $realfields) && (in_array($field, $legal) || $legal[0] == 'ALL'))
				$app[$field] = $result->$field;
		}
		$data = array('app' => $app);
		$returncode = ResponseCode::OK;
		return $data;
	}
	
	public static function action_getList($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if ($auth) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		if (!$profile->perm_app_getlist) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		$maxResults = isset($args->max_results) && $args->max_results > 0 && $args->max_results <= 60 ?
			(int)$args->max_results : 15;
		$startWith = isset($args->start_result) ? (int)$args->start_result : 1;
		$showTotal = isset($args->show_total_results) ? $args->show_total_results == 1 : false;
		$sort = isset($args->sort_by) ? $args->sort_by : 'relevance';
		$fields = isset($args->fields) && is_array($args->fields) ? $args->fields : array();
		$cat = isset($args->filter->category) ? (int)$args->filter->category : 0;
		$filter = isset($args->filter->text) ? $args->filter->text : false;
		$app_ids = isset($args->filter->app_id) ? $args->filter->app_id : false;
		$itunes_ids = isset($args->filter->itunes_id) ? $args->filter->itunes_id : false;
		$am = ApplicationModel::getInstance();
		$results = $am->getAppListCached($maxResults, false, $sort, $cat, $filter, $app_ids, $itunes_ids, $startWith);
		$legal = $profile->allowed_app_fields;
		$realfields = ShortAppBean::getFields();
		$apps = array();
		foreach ($results as $result) {
			$i = count($apps);
			$apps[$i]['id'] = $result->id;
			foreach ($fields as $field) {
				if ($field != 'id' && in_array($field, $realfields) && (in_array($field, $legal) || $legal[0] == 'ALL'))
					$apps[$i][$field] = $result->$field;
			}
		}
		$data = array('apps' => $apps);
		if ($showTotal) {
			$total = $am->getResultCountCached($cat, $filter, $app_ids, $itunes_ids);
			$data['total_results'] = $total;
		}
		$returncode = ResponseCode::OK;
		return $data;
	}
}

?>