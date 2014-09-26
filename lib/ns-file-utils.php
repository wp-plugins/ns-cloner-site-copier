<?php

require_once (dirname(__FILE__).'/ns-log-utils.php');

/*
 * Get the uploads folder for the site passed in with $id
 * 
 * HANDLE 2 POTENTIAL SCENARIOS:
 * 1) WP Install originated before 3.5 and has legacy /wp-content/blogs.dir/ storage
 * 2) WP Install originated with 3.5 or later and has /wp-content/uploads/sites/ storage
 *
 * ADD-ON MAYBE NEEDED TO HANDLE POTENTIAL 3rd SCENARIO:
 * 3) WP Install has custom uploads storage in which case it might be difficult to handle 
 * 	  Add-on has options:
 *    - use strict wp_upload_dir()
 * 	  - set manual upload_path at clone time (auto-fill from query on option_value in 
 *  	wp_##_options where option_name = 'upload_path')
 *    - TODO: might need to add a filter to this to allow add-ons to modify
 * 
 * NEW ALGORITHM:
 * - use 2 factors and 3 tests per factor to determine confidence level of accurate detection
 * - 2 factors: pre (test_1) or post (test_2) version 3.5 of WP Installation Origin
 * - 3 tests per factor: 
 * 	 - does the base uploads directory of the factor exist?
 *   - does a hypothetical site uploads dir of the factor based on $id exist?
 *   - does the hypothetical site uploads dir of the factor match the result of wp_uploads_dir()? 
 * - establish and return an $upload_dir in descending order of confidence based on how many 
 *   tests per fact are true 
 */


function ns_get_upload_dir( $id, $logfile=false ) {
// ---------------------------------------------------------------------------------------------------------------------
//										T E S T I N G  	 N E W    C O D E 
// ---------------------------------------------------------------------------------------------------------------------
	// initialize
	switch_to_blog($id);
	$wp_upload_dir_a = wp_upload_dir();
	restore_current_blog();
	$wp_upload_dir = ns_norm_winpath($wp_upload_dir_a['basedir']);
	$upload_dir = ''; // eventual return value
	
	// handle difference between ID = 1 and everything else
	// in ID = 1 test_1 and test_2 are identical
	if ($id == 1) {
		$test_1_base = ns_norm_winpath(WP_CONTENT_DIR . '/uploads');
		$test_1_upload_dir = ns_norm_winpath($test_1_base);
		$test_2_base = ns_norm_winpath(WP_CONTENT_DIR . '/uploads');
		$test_2_upload_dir = ns_norm_winpath($test_2_base);	
	} else {
		$test_1_base = ns_norm_winpath(WP_CONTENT_DIR . '/blogs.dir');
		$test_1_upload_dir = ns_norm_winpath($test_1_base . '/' . $id);
		$test_2_base = ns_norm_winpath(WP_CONTENT_DIR . '/uploads/sites');
		$test_2_upload_dir = ns_norm_winpath($test_2_base . '/' . $id);
	}
	
	// compare and set conditions for determining confidence level
	$test_1_base_exists = (file_exists($test_1_base) ? true : false);
	$test_1_upload_dir_exists = (file_exists($test_1_upload_dir) ? true : false);
	$test_1_matches_wp = ($test_1_upload_dir == $wp_upload_dir ? true : false); 
	$test_2_base_exists = (file_exists($test_2_base) ? true : false);
	$test_2_upload_dir_exists = (file_exists($test_2_upload_dir) ? true : false);
	$test_2_matches_wp = ($test_2_upload_dir == $wp_upload_dir ? true : false);
	$wp_upload_dir_exists = (file_exists($wp_upload_dir) ? true : false);
	
	// cascade in order of confidence
	// HIGH CONFIDENCE ----------------------------------------------------------------------------------
	// WP Origin < 3.5
	if ($test_1_base_exists && $test_1_upload_dir_exists && $test_1_matches_wp) { 
		$upload_dir = $test_1_upload_dir;
		$confidence = 'HIGH CONFIDENCE';
	}
	// WP Origin > 3.5 (fall through if $upload_dir already set)
	if ($upload_dir == '' && $test_2_base_exists && $test_2_upload_dir_exists && $test_2_matches_wp) { 
		$upload_dir = $test_2_upload_dir;
		$confidence = 'HIGH CONFIDENCE';
	}
	// MEDIUM CONFIDENCE --------------------------------------------------------------------------------
	// WP Origin < 3.5 (fall through if $upload_dir already set)
	if ($upload_dir == '' && $test_1_base_exists && $test_1_matches_wp) { 
		$upload_dir = $test_1_upload_dir;
		$confidence = 'MEDIUM CONFIDENCE';
	}
	// WP Origin > 3.5 (fall through if $upload_dir already set)
	if ($upload_dir == '' && $test_2_base_exists && $test_2_matches_wp) { 
		$upload_dir = $test_2_upload_dir;
		$confidence = 'MEDIUM CONFIDENCE';
	}
	// LOW CONFIDENCE -----------------------------------------------------------------------------------
	// WP Origin < 3.5 (fall through if $upload_dir already set)
	if ($upload_dir == '' && $test_1_base_exists) { 
		$upload_dir = $test_1_upload_dir;
		$confidence = 'LOW CONFIDENCE';
	}
	// WP Origin > 3.5 (fall through if $upload_dir already set)
	if ($upload_dir == '' && $test_2_base_exists) { 
		$upload_dir = $test_2_upload_dir;
		$confidence = 'LOW CONFIDENCE';
	}
	// FAIL SAFE ----------------------------------------------------------------------------------------
	if ($upload_dir == '') {
		$upload_dir = $wp_upload_dir;
		$confidence = 'FAIL SAFE';
	}
	
	// log results for debugging
	ns_log_section_break($logfile);
	ns_log_write('TESTING UPLOAD LOCATION for ID = ' . $id, $logfile);
	ns_log_section_break($logfile);
	ns_log_write('wp_upload_dir	     = ' . $wp_upload_dir . ns_t2e($wp_upload_dir_exists), $logfile);
	ns_log_write('test_1_base        = ' . $test_1_base . ns_t2e($test_1_base_exists), $logfile);
	ns_log_write('test_1_upload_dir  = ' . $test_1_upload_dir . ns_t2e($test_1_upload_dir_exists), $logfile);
	ns_log_write('test_1_matches_wp  = ' . ns_t2e($test_1_matches_wp), $logfile);
	ns_log_write('test_2_base        = ' . $test_2_base . ns_t2e($test_2_base_exists), $logfile);
	ns_log_write('test_2_upload_dir  = ' . $test_2_upload_dir . ns_t2e($test_2_upload_dir_exists), $logfile);	
	ns_log_write('test_2_matches_wp  = ' . ns_t2e($test_2_matches_wp), $logfile);
	ns_log_write('<b>upload_dir = ' . $upload_dir . '</b> with ' . $confidence, $logfile);
	ns_log_section_break ($logfile);
	
	return $upload_dir ;
}

/*
 * Get the URL for uploads for a site - V2 didn't have advanced checking like above on this so not adding it for now
 */
function ns_get_upload_url( $id, $logfile=false ){		
	switch_to_blog($id);
	$wp_upload_dir_a = wp_upload_dir();
	restore_current_blog();
	return $wp_upload_dir_a['baseurl'];
}

/**
 * Check for windows-based systems and normalize if necessary
 */
function ns_norm_winpath( $path ) {
	if (strpos($path ,'/') !== false && strpos($path ,'\\') !== false ) {
		return str_replace('/', '\\', $path );
	} else {
		return $path;	
	}	
}

/**
 * Translate boolean to exists or not exists
 */
function ns_t2e( $bool ) {
	if ($bool) {
		return ' <span style="color:green"><b>(EXISTS)</b></span>';
	} else {
		return ' <span style="color:red">(NOT EXISTS)</span>';
	} 
}

/**
 * Copy directories and files recursively and return number of copies executed
 * Skip directories called 'sites' to avoid copying all sites storage in WP > 3.5
 * @return int number of files copied
 */
function ns_recursive_dir_copy( $src, $dst, $num=0 ) {
	$num = $num + 1;
	if( is_dir($src) ){
		if( !file_exists($dst) ){
			mkdir($dst);
		}
		$files = scandir($src);
		foreach ($files as $file){
			if ($file != "." && $file != ".." && $file != 'sites'){
				$num = ns_recursive_dir_copy("$src/$file", "$dst/$file", $num);
			}
		} 
	}
	elseif( file_exists($src) ){
		copy($src, $dst);
	}
	return $num;
}

/**
 * Return an array of all blog upload dir paths
 */
function ns_get_multisite_upload_paths( $args=array('limit'=>9999) ){
	$upload_paths = array();
	$sites = function_exists('wp_get_sites')? wp_get_sites($args) : get_blog_list(0,'all');
	foreach( $sites as $site ){
		switch_to_blog($site['blog_id']);
		$wp_upload_dir = wp_upload_dir();
		$upload_paths[ $site['blog_id'] ] = $wp_upload_dir['basedir'];
		restore_current_blog();
	}
	return $upload_paths;
}

?>