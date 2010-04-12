<?php
	use hydrogen\config\Config;
	use appdb\usersession\UserSession;
	use appdb\models\ApplicationModel;
	use appdb\models\AppCategoryModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'valid' => '0',
		'error' => 'Server error.  Try again soon!'
		)));
	
	if (!class_exists('hydrogen\config\Config', false))
		die("Quit trying to hack my damn code.");
	
	if (!UserSession::getPermission('view_all_apps')) {
		$jsonError = array(
			'valid' => '0',
			'error' => 'You do not have permission to browse applications.'
			);
		die(json_encode($jsonError));
	}
	
	$legalSorts = array(
		'newvers',
		'newapps',
		'appname',
		'relevance'
		);
	
	$perpage = isset($_GET['perpage']) ? $_GET['perpage'] : 15;
	if ($perpage != 15 && $perpage != 30 && $perpage != 60)
		$perpage = 15;
	$perpage += 0; // Ensures we're not using a string that starts with a number
		
	$sort = isset($_GET['sort']) ? $_GET['sort'] : $legalSorts[0];
	$goodSort = false;
	foreach ($legalSorts as $type) {
		if ($sort == $type) {
			$goodSort = true;
			break;
		}
	}
	if (!$goodSort)
		$sort = $legalSorts[0];
		
	$cat = isset($_GET['cat']) ? $_GET['cat'] : 0;
	$cat += 0;
	if ($cat < 0)
		$cat = 0;
	
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if ($page < 1)
		$page = 1;
	$page += 0;
	
	$filter = isset($_GET['filter']) && $_GET['filter'] != '*' && 
		!Config::getVal('general', 'disable_search') ? strtolower(trim($_GET['filter'])) : '';
	if (!$filter && $sort == "relevance")
		$sort = "newvers";
		
	$am = ApplicationModel::getInstance();
	$apps = $am->getAppListCached($perpage, $page, $sort, $cat, $filter);
	if (!$apps) {
		$jsonError = array(
			'valid' => '0',
			'error' => 'No applications matched your request.'
			);
		die(json_encode($jsonError));
	}
	$total = $am->getResultCountCached($cat, $filter);
	if ($sort != 'appname' && $sort != 'relevance') {
		$rsslink = Config::getVal('urls', 'base_url') . '/';
		if (Config::getVal('rewrite', 'rss_options', false) != '') {
			$rsslink .= Config::getVal('rewrite', 'rss_options');
			$rsslink = str_replace(array(
				'%RESULTS%',
				'%SORT%',
				'%CAT%',
				'%FILTER%',
				'%TYPE%'
				), array(
				'15',
				$sort,
				$cat,
				($filter == '' ? '*' : $filter),
				'html'
				),
				$rsslink);
		}
		else
			$rsslink .= "rss.php?results=15&sort=$sort&cat=$cat&filter=$filter&type=html";
	}
	else
		$rsslink = 0;
	
	$jsonPage = array(
		'valid' => 1,
		'perpage' => $perpage,
		'sort' => $sort,
		'page' => $page,
		'category' => $cat,
		'filter' => $filter,
		'rss' => $rsslink,
		'totalapps' => $total,
		'categories' => array(),
		'apps' => array()
		);
	
	$acm = AppCategoryModel::getInstance();
	$cats = $acm->getAllCached();
	foreach ($cats as $bean)
		$jsonPage['categories']["$bean->id"] = $bean->category_name;
	foreach ($apps as $app) {
		$catbean = $app->getMapped('category');
		$jsonPage['apps'][] = array(
			'app_id' => $app->id,
			'name' => $app->name,
			'company' => $app->company,
			'category' => $catbean->category_name,
			'version' => $app->latest_version,
			'smallicon' => $app->smallicon_url
			);
	}
	
	echo json_encode($jsonPage);
?>