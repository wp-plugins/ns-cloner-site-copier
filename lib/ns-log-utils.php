<?php

require_once (dirname(__FILE__).'/kint/Kint.class.php');

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

function ns_log_open( $logfile ){
	$open =
<<<HTML
<html>
	<head>
		<style>
			table{ width:100%; border: solid 1px #ddd; }
			td{ padding: 0 .9em; border-bottom: solid 1px #ddd; }
			td:first-child{ background:#ddd; font:bold .9em monospace; text-align:center; color:#555; width:3em; }
			td span{ float:left; display:inline-block; padding: 4px 6px 4px 0; }
			.kint{ display:inline-block; margin: 0 0 0 6px !important; }
			.kint footer{ display:none; }
		</style>
	</head>
	<body>
		<table cellspacing="0" cellpadding="4" width="100%">
HTML;
	error_log( $open, 3, $logfile );
}

function ns_log_write( $message, $logfile  ) {
	global $ns_log_start_time;
	// save start time if it's not set so we can compare against it for getting current relative # of seconds into clone
	if( is_null($ns_log_start_time) ){
		$ns_log_start_time = microtime(true);
	}
	if( !is_writable($logfile) ){
		ns_log_check( $logfile );
		if( !is_writable($logfile) ){
			return false;
		}
	}
	// calculate current time into process and set up message
	$current_seconds = number_format( microtime(true) - $ns_log_start_time, 4 );
	$formatted_message = "";
	foreach( (array)$message as $message_part ){
		$formatted_message .= is_string($message_part)? "<span>$message_part</span>" : @Kint::dump($message_part);
	}
	// log it!
	$time_cell = "<td>{$current_seconds}s</td>";
	$message_cell = "<td>{$formatted_message}</td>";
	error_log( "<tr>{$time_cell}{$message_cell}</tr>", 3, $logfile );
}

function ns_log_close( $logfile ){
	$close =
<<<HTML
		</table>
	</body>
</html>
HTML;
	error_log( $close, 3, $logfile );
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
	foreach( get_plugins() as $plugin_file=>$data ){
		ns_log_write( "$data[Name] $data[Version] by $data[Author]".( $data["Network"]==true? " <strong>Network Enabled</strong>" : ""), $logfile );
	}
	
}

?>