<?php
/*
 * AppDB
 * api/getcategories.php
 * Kyek
 * September 25, 2008
 */

	// Includes
	require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
	use appdb\models\AppCategoryModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'successful' => 0,
		'error' => 'Server error'
		)));
	
	// Start us up
	header('Content-type: text/plain; charset=utf-8');
	
	$acm = AppCategoryModel::getInstance();
	$cats = $acm->getAllCached();
	$result = array();
	if (!$cats) {
		$result['successful'] = 0;
		$result['error'] = 'Unable to retrieve categories';
	}
	else {
		$result['successful'] = 1;
		foreach ($cats as $cat)
			$result["$cat->id"] = $cat->category_name;
	}
	
	die(json_encode($result));
?>