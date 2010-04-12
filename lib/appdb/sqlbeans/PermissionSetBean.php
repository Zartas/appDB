<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class PermissionSetBean extends SQLBean {
	protected static $tableNoPrefix = 'permissionsets';
	protected static $tableAlias = 'ps';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'permissionset_name',
		'view_all_apps',
		'view_unscrambled_links',
		'submit_new_itunes_apps',
		'submit_free_itunes_apps',
		'submit_new_manual_apps',
		'submit_unknown_app_versions',
		'submit_links_existing_apps',
		'approve',
		'edit_own_links',
		'edit_all_links',
		'edit_itunes_apps',
		'edit_manual_apps',
		'view_all_posters',
		'ip_ban_immune'
		);
}

?>