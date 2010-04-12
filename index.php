<?php
/*
 * AppDB
 * index.php
 * Kyek
 * August 6, 2008
 *
 * Reworked with V2 core
 * June 22, 2009
 */

	// Includes
	require_once(__DIR__ . '/lib/appdb/appdb.inc.php');
	use hydrogen\config\Config;
	use hydrogen\errorhandler\ErrorHandler;
	use appdb\usersession\UserSession;
	
	// Are we mobile?
	if (preg_match('/(iPod|iPhone)/', $_SERVER['HTTP_USER_AGENT']))
		die(header('Location: ' . Config::getVal('urls', 'base_url') . '/i'));
	
	// Open up our session
	UserSession::open_session();
	
	// Honor any requests to log out
	if (isset($_GET['action']) && $_GET['action'] == "logout")
		UserSession::logout();
	
	// Get the user
	$user = UserSession::getUserBean();
		
	// Turn off caching
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	
	// Detect if this is an AJAX call and handle appropriately
	if (isset($_GET['call']) && isset($_GET['calltype']) && $_GET['calltype'] == 'ajax') {
		$validAjax = array(
			'applisting',
			'appsubmit',
			'itunesinfo',
			'login',
			'register'
			);
		$handler = false;
		foreach ($validAjax as $callopt) {
			if ($callopt == $_GET['call']) {
				$handler = true;
				break;
			}
		}
		if (!$handler)
			die();
		header('Content-type: text/plain; charset=utf-8');
		require(__DIR__ . '/ajax/' . $_GET['call'] . '.php');
		die();
	}
	
	// Switch to html, utf-8
	header('Content-type: text/html; charset=utf-8');
	
	// Find out what they want
	$pagevalid = false;
	$page = isset($_GET['page']) ? $_GET['page'] : Config::getVal('general', 'default_page');
	$pages = array(
		'applist',
		'about',
		'submit',
		'learnmore',
		'changes',
		'jscript',
		'viewapp',
		'login',
		'terms',
		'domains',
		'newrelease');
	
	foreach ($pages as $p) {
		if ($p == $page) {
			$pagevalid = true;
			break;
		}
	}
	if (!$pagevalid)
		$page = Config::getVal('general', 'default_page');
		
	// List all javascript includes
	$js_incl['jquery'] = '<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>';
	$js_incl['facebox'] = '<script type="text/javascript" src="js/facebox.min.js"></script>';
	$js_incl['submit'] = '<script type="text/javascript" src="js/submit.min.js"></script>';
	$js_incl['applist'] = '<script type="text/javascript">var search = ' . (Config::getVal('general', 'disable_search', false) ? 'false' : 'true') .
		';</script><script type="text/javascript" src="js/applist.pack.js"></script>';
	$js_incl['login'] = '<script type="text/javascript" src="js/login.min.js"></script>';
	$js_incl['recaptcha'] = '<script type="text/javascript" src="http://api.recaptcha.net/js/recaptcha_ajax.js"></script>';
	$js_incl['fakecaptcha'] = '<script type="text/javascript" src="js/fakecaptcha.js"></script>';
	$js_incl['mint'] = '<script src="' . Config::getVal('mint', 'url') . '?js" type="text/javascript"></script>';
	
	// List all css includes
	$css_incl['global'] = '<link rel="stylesheet" href="css/style.css" type="text/css" media="screen" charset="utf-8" />';
	$css_incl['ie'] = '<!--[if lte IE 7]><link rel="stylesheet" href="css/styleIE.css" type="text/css" media="screen" charset="utf-8" /><![endif]-->';
	$css_incl['facebox'] = '<link rel="stylesheet" href="css/facebox.css?1" type="text/css" media="screen" charset="utf-8" />';
		
	// Set up the appropriate javascript includes
	$header_js = '';
	if (Config::getVal('mint', 'enabled'))
		$header_js .= $js_incl['mint'];
	$header_css = $css_incl['global'];
	switch ($page) {
		case 'applist':
			$header_js .= $js_incl['jquery'] . "\n" . $js_incl['applist'] . "\n" . $js_incl['facebox'];
			$header_css .= "\n" . $css_incl['facebox'];
			break;
		case 'submit':
			$header_js .= $js_incl['jquery'] . "\n" . $js_incl['submit'];
			break;
		case 'login':
			if (isset($_GET['action']) && $_GET['action'] == 'verify')
				$header_js .= $js_incl['fakecaptcha'] . "\n";
			else
				$header_js .= $js_incl['recaptcha'] . "\n";
			$header_js .= $js_incl['jquery'] . "\n" . $js_incl['login'];
			break;
	}
	$header_css .= "\n" . $css_incl['ie'];
	
	// If we're using javascript, let's push our constants out
	if ($header_js != '') {
		$js_pre = '<script type="text/javascript">' . "\n";
		$js_pre .= "\t" . 'var BASE_URL = "' . Config::getVal('urls', 'base_url') . '/";' . "\n";
		if ($page == 'login')
			$js_pre .= "\t" . 'var RECAPTCHA_KEY = "' . Config::getVal('recaptcha', 'public_key') . '";' . "\n";
		if ($page == 'submit') {
			$domainstr = 'var allowedDomains = new Array(';
			foreach (Config::getVal('domains', 'allowed', false) as $aldm)
				$domainstr .= '"' . $aldm . '", ';
			$domainstr = substr($domainstr, 0, strlen($domainstr) - 2) . ');';
			$js_pre .= "\t$domainstr\n"; 
		}
		$js_pre .= '</script>' . "\n";
		$header_js = $js_pre . $header_js;
	}
	
	// Include the right RSS url
	$rssurl = Config::getVal('urls', 'base_url') . '/';
	if (Config::getVal('rewrite', 'rss') != '')
		$rssurl .= Config::getVal('rewrite', 'rss');
	else
		$rssurl .= 'rss.php';
		
	$rsstypeurl = Config::getVal('urls', 'base_url') . '/';
	if (Config::getVal('rewrite', 'rss_type') != '')
		$rsstypeurl .= Config::getVal('rewrite', 'rss_type');
	else
		$rsstypeurl .= 'rss.php?type=%TYPE%';
		
	$rssbbcodeurl = preg_replace('/\\%TYPE\\%/', 'bbcode', $rsstypeurl);
	
	// Get the requested page
	ob_start();
	require('pages/' . $page . '.php');
	$content = ob_get_contents();
	ob_end_clean();
	
	// Can we get a page title?
	$pagetitle = Config::getVal('general', 'site_name') . " : The iPhone and iPod Touch Application Index";
	$match = preg_match('/<!--\[PAGETITLE\]>([^<]+)<\[\/PAGETITLE\]-->/', $content, $matches);
	if ($match) {
		$pagetitle = $matches[1] . ' :: ' . Config::getVal('general', 'site_name');
		$content = preg_replace('/<!--\[PAGETITLE\]>([^<]+)<\[\/PAGETITLE\]-->/', '', $content);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo $pagetitle; ?></title>
	<link rel="shortcut icon" type="image/png" href="/favicon.png" />
	<link rel="icon" type="image/png" href="/favicon.png" />
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png"/>
	<link rel="alternate" type="application/rss+xml" title="Newest Apps and Updates (Realtime, HTML)" href="<?php echo $rssurl; ?>" />
	<link rel="alternate" type="application/rss+xml" title="Newest Apps and Updates (Realtime, BBCode)" href="<?php echo $rssbbcodeurl; ?>" />
	<script type="text/javascript">
		if (top.location != location)
		    top.location.href = document.location.href;
		if (top.location.href != window.location.href)
			top.location.href = window.location.href;
	</script>
	<?php echo $header_css . "\n" . $header_js; ?>
</head>
<body>
	<div id="wrap">
		<div id="header">
			<div id="logo">
				<a href="<?php echo Config::getVal('urls', 'base_url'); ?>"><img src="images/betamast.png" alt="Appulo.us" /></a>
			</div>
			<div id="account">
				<?php
					if ($user) {
						echo 'Logged in as <span class="username">' . $user->username . '</span>. ' .
							'<span class="logout">[<a href="?action=logout">Log out</a>]</span>';
					}
					else {
						echo 'You are not logged in. <a href="?page=login">Fix that.</a>';
					}
				?>
			</div>
			<div id="menubar">
				<ul>
					<li><a href="?page=about"<?php if ($page == 'about') echo ' class="curpage"'; ?>>About <?php echo Config::getVal('general', 'site_name'); ?>: FAQ</a></li>
					<li><a href="?page=learnmore"<?php if ($page == 'learnmore') echo ' class="curpage"'; ?>>Learn More</a></li>
					<li><a href="?page=changes"<?php if ($page == 'learnmore') echo ' class="curpage"'; ?>>Upcoming Changes</a></li>
					<li><a href="?page=submit"<?php if ($page == 'submit') echo ' class="curpage"'; ?>>Submit an App</a></li>
				</ul>
			</div>
		</div>
		<div id="content">
			<?php echo $content; ?>
		</div>
		<div id="footer"<?php if ($page == 'submit') echo ' class="footerup"'; ?>>
			<span id="copyright"><?php echo Config::getVal('general', 'site_name'); ?>, its logo, website, and web application are Copyright &copy;2008 <?php echo Config::getVal('general', 'site_name'); ?>.  iPhone and iPod Touch are registered trademarks of Apple, Inc.  All other contents are copyrighted and trademarked by their respective owners.</span>
		</div>
	</div>
	<?php /*
	<script type="text/javascript">
	<?php
		if ($userInfo->is_user) {
			echo "var woopra_array = new Array();\n";
			echo "woopra_array['name'] = '$userInfo->username';\n";
			echo "woopra_array['email'] = '$userInfo->email';\n";
		}
		else if (isset($_COOKIE['appdb_username'])) {
			echo "var woopra_array = new Array();\n";
			echo "woopra_array['name'] = '$_COOKIE[appdb_username]';\n";
		}
	?>
	var _wh = ((document.location.protocol=='https:') ? "https://sec1.woopra.com" : "http://static.woopra.com");
	document.write(unescape("%3Cscript src='" + _wh + "/js/woopra.js' type='text/javascript'%3E%3C/script%3E"));
	</script> */ ?>
</body>
</html>
