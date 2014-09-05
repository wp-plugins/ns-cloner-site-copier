<?php

/**
 * Check writeability
 */
function ns_log_check( $logfile, $savefile=true ) {
	if( !empty($logfile) && !file_exists( $logfile ) ) {
		$handle = @fopen( $logfile, 'w' ) or ns_add_admin_notice( 
			sprintf( __( 'Unable to create log file %s. Functionality will work, but you won\'t have logs in case anything needs debugging. Is that file\'s parent directory writable by the server?', 'ns-cloner' ), $logfile ),
			"error",
			"all",
			is_network_admin()
		);
		@fclose( $handle );
		if( file_exists($logfile) && !$savefile ){
			unlink( $logfile );
		}
	}
}

/**
 * Log
 */
function ns_log_write( $message, $logfile, $linebreak_after=true ) {
	if( !is_writable($logfile) ){
		ns_log_check( $logfile );
		if( !is_writable($logfile) ){
			return false;
		}
	}
	$start = $linebreak_after? date_i18n('Y-m-d H:i:s')." - " : ""; 
	$end = $linebreak_after? "\n<br/>" : "\n";
	error_log( $start.$message.$end, 3, $logfile );
}

/**
 * Log Section Break
 */
function ns_log_section_break( $logfile) {
	ns_log_write( "-----------------------------------------------------------------------------------------------------------", $logfile );
}

/**
 * Diagnostics
 */
function ns_diag ( $logfile ) {
	global $wp_version, $wp_db_version, $required_php_version, $required_mysql_version;
	
	ns_log_write( "ENVIRONMENT DIAGNOSTICS:", $logfile );
	ns_log_write( "Web Server Info:", $logfile );
	ns_log_write( "PHP Version Required: <strong>" . $required_php_version . " </strong>", $logfile );
	ns_log_write( "PHP Version Current: <strong>" . phpversion() . " </strong>", $logfile );
	ns_log_write( "MySQL Version Required: <strong>" . $required_mysql_version . " </strong>", $logfile );
	ns_log_write( "MySQL Version Current: <strong>" . ns_get_mysql_variable( 'version' ) . " </strong>", $logfile );
	ns_log_write( "WP Version: <strong>$wp_version</strong>", $logfile );
	ns_log_write( "WP Memory Limit: <strong>" . WP_MEMORY_LIMIT . " </strong>", $logfile );
	ns_log_write( "WP Debug Mode: <strong>" . WP_DEBUG . " </strong>", $logfile );
	ns_log_write( "WP Multisite: <strong>" . MULTISITE . " </strong>", $logfile );
	ns_log_write( "WP Subdomain Install: <strong>" . SUBDOMAIN_INSTALL . " </strong>", $logfile );
	ns_log_write( "PHP Post Max Size: <strong>" . ini_get('post_max_size') . " </strong>", $logfile );
	ns_log_write( "PHP Upload Max Size: <strong>" . ini_get('upload_max_size') . " </strong>", $logfile );
	ns_log_write( "PHP Memory Limit: <strong>" . ini_get('memory_limit') . " </strong>", $logfile );
	ns_log_write( "PHP Max Input Vars: <strong>" . ini_get('max_input_vars') . " </strong>", $logfile );
	ns_log_write( "PHP Max Execution Time: <strong>" . ini_get('max_execution_time') . " </strong>", $logfile );
	ns_log_section_break( $logfile );
	
	ns_log_write( "PLUGIN DIAGNOSTICS:", $logfile );
	ns_log_write( "<ul>", $logfile, false );
	foreach( get_plugins() as $plugin_file=>$data ){
		ns_log_write(
			"<li>".
				"$data[Name] $data[Version] by $data[Author]".
				( $data["Network"]==true? " <strong>Network Enabled</strong>" : "" ).
			"</li>",
			$logfile,
			false
		);
	}
	ns_log_write( "</ul>", $logfile, false );

	
}

?>