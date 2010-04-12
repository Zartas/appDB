<?php
/*
 * install.php
 * Kyek
 * August 7, 2008
 *
 * Installs the MySQL tables for AppDB.  Delete after using!
 */

	// Get our engine
	require_once('../lib/hydrogen/hydrogen.inc.php');
	use hydrogen\config\Config;
	$db = hydrogen\database\DatabaseEngineFactory::getEngine();
	
	/* In order to allow this database to operate under heavy load, some data
	   must be duplicated into this table to use for the 'façade'.  This breaks
	   the cardinal rule of database design, but it's the only way to keep this
	   app operating.  As a result, this information must be checked and updated
	   any time a change is made to the versions or links table. */
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'applications (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		itunes_id BIGINT NOT NULL,
		name VARCHAR(200) NOT NULL,
		category_id INT(10) NOT NULL,
		company VARCHAR(200) DEFAULT NULL,
		releasedate VARCHAR(20) DEFAULT NULL,
		seller VARCHAR(200) DEFAULT NULL,
		size VARCHAR(20) DEFAULT NULL,
		price VARCHAR(20) DEFAULT NULL,
		description TEXT DEFAULT NULL,
		languages TEXT DEFAULT NULL,
		requirements TEXT DEFAULT NULL,
		smallicon_url VARCHAR(500) DEFAULT NULL,
		bigicon_url VARCHAR(500) DEFAULT NULL,
		artwork_url VARCHAR(500) DEFAULT NULL,
		date_added DATETIME NOT NULL,
		last_updated DATETIME NOT NULL,
		latest_version VARCHAR(40) DEFAULT NULL,
		latest_version_first_cracker VARCHAR(60) DEFAULT NULL,
		latest_version_added DATETIME DEFAULT NULL,
		PRIMARY KEY(id),
		INDEX name(name),
		INDEX date_added(date_added),
		INDEX latest_version_added(latest_version_added),
		UNIQUE INDEX itunes_id(itunes_id),
		FULLTEXT (name, description)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'search_name_index (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		term VARCHAR(16) NOT NULL,
		app_id_array TEXT NOT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX term(term)
		)');
	
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'versions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		app_id INT(10) UNSIGNED NOT NULL,
		version VARCHAR(10) NOT NULL,
		intversion BIGINT UNSIGNED NOT NULL,
		versioninfo TEXT DEFAULT NULL,
		date_added DATETIME NOT NULL,
		last_updated DATETIME NOT NULL,
		PRIMARY KEY(id),
		INDEX intversion(intversion),
		UNIQUE INDEX appver(app_id, version)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'links (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		app_id INT(10) UNSIGNED NOT NULL,
		version_id INT(10) UNSIGNED DEFAULT NULL,
		filetype TINYINT UNSIGNED NOT NULL DEFAULT 0,
		cracker VARCHAR(25) DEFAULT NULL,
		url VARCHAR(500) NOT NULL,
		clicks INT(10) UNSIGNED NOT NULL DEFAULT 0,
		date_added DATETIME NOT NULL,
		last_updated DATETIME NOT NULL,
		submitter_id INT(10) UNSIGNED NOT NULL,
		submitter_ip VARCHAR(15) NOT NULL,
		submitted_from VARCHAR(32) NOT NULL DEFAULT \'Web\',
		approvedby_id INT(10) UNSIGNED DEFAULT NULL,
		approved_on DATETIME DEFAULT NULL,
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY(id),
		INDEX app_id(app_id)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'users (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		username VARCHAR(20) NOT NULL,
		email VARCHAR(90) NOT NULL,
		group_id INT(10) UNSIGNED NOT NULL,
		permission_override_id INT(10) UNSIGNED NOT NULL DEFAULT 1,
		password VARCHAR(32) NOT NULL,
		salt VARCHAR(5) NOT NULL,
		joindate DATETIME NOT NULL,
		lastlogin DATETIME DEFAULT NULL,
		reg_ip VARCHAR(15) NOT NULL,
		last_ip VARCHAR(15) NOT NULL,
		must_validate TINYINT(1) UNSIGNED NOT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX username(username)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'categories (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		category_name VARCHAR(60) NOT NULL,
		date_added DATETIME NOT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX category_name(category_name)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'screenshots (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		app_id INT(10) UNSIGNED NOT NULL,
		shot_url VARCHAR(500) NOT NULL,
		is_horiz TINYINT(1) NOT NULL,
		date_added DATETIME NOT NULL,
		PRIMARY KEY(id),
		INDEX app_id(app_id)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'groups (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		group_name VARCHAR(60) NOT NULL,
		permissionset_id INT(10) UNSIGNED NOT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX group_name(group_name)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'permissionsets (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		permissionset_name VARCHAR(60) NOT NULL,
		view_all_apps TINYINT(1) DEFAULT NULL,
		view_unscrambled_links TINYINT(1) DEFAULT NULL,
		submit_new_itunes_apps TINYINT(1) DEFAULT NULL,
		submit_free_itunes_apps TINYINT(1) DEFAULT NULL,
		submit_new_manual_apps TINYINT(1) DEFAULT NULL,
		submit_links_existing_apps TINYINT(1) DEFAULT NULL,
		submit_unknown_app_versions TINYINT(1) DEFAULT NULL,
		approve TINYINT(1) DEFAULT NULL,
		edit_own_links TINYINT(1) DEFAULT NULL,
		edit_all_links TINYINT(1) DEFAULT NULL,
		edit_itunes_apps TINYINT(1) DEFAULT NULL,
		edit_manual_apps TINYINT(1) DEFAULT NULL,
		view_all_posters TINYINT(1) DEFAULT NULL,
		ip_ban_immune TINYINT(0) DEFAULT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX permissionset_name(permissionset_name)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'autologin (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id INT(10) UNSIGNED NOT NULL,
		date_added DATETIME NOT NULL,
		last_used DATETIME DEFAULT NULL,
		last_used_ip VARCHAR(15) DEFAULT NULL,
		publickey VARCHAR(32) NOT NULL,
		privatekey VARCHAR(32) NOT NULL,
		PRIMARY KEY(id),
		UNIQUE INDEX publickey(publickey)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'bans_ip (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		ban_desc VARCHAR(200) NOT NULL,
		ip_low BIGINT NOT NULL,
		ip_high BIGINT NOT NULL,
		permissionset_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
		date_banned DATETIME NOT NULL,
		PRIMARY KEY(id),
		INDEX ip_low(ip_low)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'bans_email (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		ban_desc VARCHAR(200) NOT NULL,
		email VARCHAR(90) NOT NULL,
		date_banned DATETIME NOT NULL,
		PRIMARY KEY(id),
		INDEX email(email)
		)');
		
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'baddies (
	    id BIGINT NOT NULL auto_increment,
	    ip VARCHAR(15) NOT NULL,
	    useragent VARCHAR(500) NOT NULL,
	    request VARCHAR(500) NOT NULL,
	    visited DATETIME NOT NULL,
		PRIMARY KEY(id),
	    INDEX ip(ip),
	    INDEX request(request)
	    )');
	
	$db->exec('CREATE TABLE IF NOT EXISTS ' . Config::getVal('database', 'table_prefix') . 'apiprofiles (
		id BIGINT NOT NULL auto_increment,
		owner_id BIGINT NOT NULL,
		name VARCHAR(32) NOT NULL,
		priv_pem TEXT NOT NULL,
		created DATETIME NOT NULL,
		active TINYINT(1) NOT NULL DEFAULT 0,
		allowed_app_fields TEXT NOT NULL,
		perm_allow_rapidfire TINYINT(1) NOT NULL DEFAULT 0,
		perm_allow_multiple_ips TINYINT(1) NOT NULL DEFAULT 0,
		perm_app_getlist TINYINT(1) NOT NULL DEFAULT 0,
		perm_app_getdetails TINYINT(1) NOT NULL DEFAULT 0,
		perm_category_list TINYINT(1) NOT NULL DEFAULT 0,
		perm_user_checkauth TINYINT(1) NOT NULL DEFAULT 0,
		perm_link_get TINYINT(1) NOT NULL DEFAULT 0,
		perm_link_get_auth TINYINT(1) NOT NULL DEFAULT 0,
		perm_link_get_all_versions TINYINT(1) NOT NULL DEFAULT 0,
		perm_link_submit_auth TINYINT(1) NOT NULL DEFAULT 0,
		perm_screenshot_get TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY(id),
		UNIQUE INDEX name(name)
		)');
	
	// Add the unknown category
	$acb = new hydrogen\sqlbeans\AppCategoryBean();
	$acb->category_name = 'Unknown';
	$acb->insert(true);
	
	// Establish the default groups and permissions
	$ps = new PermissionSetBean();
	$ps->permissionset_name = 'No Override';
	$ps->view_all_apps = NULL;
	$ps->submit_new_itunes_apps = NULL;
	$ps->submit_new_manual_apps = NULL;
	$ps->submit_links_existing_apps = NULL;
	$ps->approve = NULL;
	$ps->edit_own_links = NULL;
	$ps->edit_all_links = NULL;
	$ps->edit_itunes_apps = NULL;
	$ps->edit_manual_apps = NULL;
	$ps->view_all_posters = NULL;
	$ps->insert(true);
	
	$ps->permissionset_name = 'Banned';
	$ps->view_all_apps = false;
	$ps->submit_new_itunes_apps = false;
	$ps->submit_free_itunes_apps = false;
	$ps->submit_new_manual_apps = false;
	$ps->submit_links_existing_apps = false;
	$ps->approve = false;
	$ps->edit_own_links = false;
	$ps->edit_all_links = false;
	$ps->edit_itunes_apps = false;
	$ps->edit_manual_apps = false;
	$ps->view_all_posters = false;
	$ps->insert(true);

	$ps->name = "Public";
	$ps->view_all_apps = true;
	$ps->insert(true);
	
	$ps->name = "Validating";
	$ps->insert(true);
	
	$ps->name = "Member";
	$ps->submit_new_itunes_apps = true;
	$ps->submit_links_existing_apps = true;
	$ps->edit_own_links = true;
	$ps->insert(true);
	
	$ps->name = "Trusted Member";
	$ps->submit_new_manual_apps = true;
	$ps->submit_free_itunes_apps = true;
	$ps->insert(true);

	$ps->name = "The Patrol";
	$ps->approve = true;
	$ps->insert(true);
	
	$ps->name = "Moderator";
	$ps->edit_all_links = true;
	$ps->edit_manual_apps = true;
	$ps->view_all_posters = true;
	$ps->insert(true);
	
	$ps->name = "Administrator";
	$ps->edit_itunes_apps = true;
	$ps->insert(true);
?>