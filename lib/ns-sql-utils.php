<?php

require_once (dirname(__FILE__).'/ns-log-utils.php');

/**
 *  Define new extended version of the wpdb class which will only return null instead of dying on connection failure
 *  in order to have more graceful error handling
 */
class ns_wpdb extends wpdb {
	function bail($message, $error_code = '500'){
		$this->last_error = "Error either connecting to or selecting database.";
	}
}

/**
 *	Add backqouotes to tables and db-names in SQL queries from phpMyAdmin.
 */
 function ns_sql_backquote($a_name){
	if (!empty($a_name) && $a_name != '*') {
		if (is_array($a_name)) {
			$result = array();
			reset($a_name);
			while(list($key, $val) = each($a_name)) {
				$result[$key] = '`' . $val . '`';
			}
			return $result;
		} else {
			return '`' . $a_name . '`';
		}
	} else {
		return $a_name;
	}
} 


/**
 *	Better addslashes for SQL queries from phpMyAdmin.
 */
function ns_sql_addslashes($a_string = '', $is_like = FALSE){
	if ($is_like) {
		$a_string = str_replace('\\', '\\\\\\\\', $a_string);
	} else {
		$a_string = str_replace('\\', '\\\\', $a_string);
	}
	$a_string = str_replace('\'', '\\\'', $a_string);

	return $a_string;
}


/**
 * Get a MySQL variable. [Originally from the Diagnosis plugin by Gary Jones] 
 */
function ns_get_mysql_variable( $variable ) {
	global $wpdb;
	$result = $wpdb->get_row( "SHOW VARIABLES LIKE '$variable';", ARRAY_A );
	return $result['Value'];
}


?>