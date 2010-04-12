<?php
namespace appdb\models;

use appdb\sqlbeans\AppCategoryBean;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\recache\RECacheManager;
use hydrogen\database\Query;
use hydrogen\model\Model;

class AppCategoryModel extends Model {
	protected static $modelID = 'acm';
	
	public function getAll__3600() {
		$query = new Query('SELECT');
		$query->orderby('category_name', 'ASC');
		return AppCategoryBean::select($query);
	}
	
	public function getByName__3600($name) {
		$query = new Query('SELECT');
		$query->where('category_name = ?', $name);
		$cats = AppCategoryBean::select($query);
		if (!$cats)
			return false;
		return $cats[0];
	}
	
	public function create($name) {
		$cat = new AppCategoryBean();
		$cat->category_name = $name;
		$cat->set('date_added', 'NOW()', true);
		try {
			$cat->insert();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		$this->cm->clear("acm_all");
		$this->cm->clear("acm_name_$name");
		return true;
	}
}

?>