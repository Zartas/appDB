<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class NameIndexBean extends SQLBean {
	protected static $tableNoPrefix = 'search_name_index';
	protected static $tableAlias = 'sni';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'term',
		'app_id_array'
		);
}

?>