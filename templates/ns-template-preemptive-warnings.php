<?php

	//warn if max execution time is less than one minute
	$max_execution_time = intval(ini_get('max_execution_time')); 
	if(  $max_execution_time < 60 ){
		echo "<span class='ns-cloner-warning-message'>";
		printf( __('This host\'s max_execution_time is set to %d seconds - we generally recommend at least 60 seconds for running the Cloner. You may want to increase the max_execution_time in php.ini (or wherever your host supports PHP configuration updates) to avoid any timeout errors.','ns-cloner'), $max_execution_time );
		echo "</span>";
	}
	//warn if memory limit is less than 128M
	$memory_limit = intval(ini_get('memory_limit'));
	if(  $memory_limit < 128 ){
		echo "<span class='ns-cloner-warning-message'>";
		printf( __('This host\'s memory_limit is set to %dMB - we generally recommend at least 128MB for running the Cloner. You may want to increase the memory_limit in php.ini (or wherever your host supports PHP configuration updates) to avoid any out-of-memory errors.','ns-cloner'), $memory_limit );
		echo "</span>";
	}

	// warn if .htaccess does not contain multisite file rewrite (but only if not on iis7)
	if( !iis7_supports_permalinks() ){
		// foolproof htaccess path detection stolen from wp-admin/includes/network.php
		$slashed_home      = trailingslashit( get_option( 'home' ) );
		$base              = parse_url( $slashed_home, PHP_URL_PATH );
		$document_root_fix = str_replace( '\\', '/', realpath( $_SERVER['DOCUMENT_ROOT'] ) );
		$abspath_fix       = str_replace( '\\', '/', ABSPATH );
		$home_path         = 0 === strpos( $abspath_fix, $document_root_fix ) ? $document_root_fix . $base : get_home_path();
		$htaccess          = file_get_contents( $home_path.'.htaccess' );
		// set patterns which tell us that multisite file rewrite is there
		$pre_3_5_rewrite = 'wp-includes/ms-files.php';
		$post_3_5_rewrite = '(wp-(content|admin|includes)';
		// show error if neither pattern occurs
		if( strpos($htaccess,$pre_3_5_rewrite)===false && strpos($htaccess,$post_3_5_rewrite)===false ){
			echo "<span class='ns-cloner-warning-message'>";
			printf( __('It appears that you have a non-standard (possibly incorrect) .htaccess file for a multisite install. Cloned sites will not work if rewrites are not configured correctly. Please check the recommended htaccess settings <a href="%s" target="_blank">here</a> and make sure your .htaccess file matches.','ns-cloner'), admin_url('/network/setup.php') );
			echo "</span>";
		}		
	}

	// warn if other plugin installed that adds additional wpmu_validate_blog_signup requirements (like Site Templates)
	$test_blog_validation = wpmu_validate_blog_signup( 'nsclonervalidationtest', 'NS Cloner Test' );
	$errors = $test_blog_validation['errors']->get_error_messages();
	if( !empty($errors) ){
		$errors_string = '"' . join( '","', $errors ) . '"';
		echo "<span class='ns-cloner-warning-message'>";
		_e( 'It looks like you have another plugin installed which is applying its own additional site-meta creation requirements. The Cloner will still work, but you should be aware that sites created with the Cloner might not appear normally in the other plugin.', 'ns-cloner' );
		echo "</span>";
	}

?>