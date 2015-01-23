<?php

require_once (dirname(__FILE__).'/ns-log-utils.php');

/**
 * Create site
 */
function ns_wp_create_site( $site_name, $site_title, $logfile ) {
	$user = apply_filters( 'ns_wp_create_site_admin', wp_get_current_user() );
	$site_meta = apply_filters( 'ns_wp_create_site_meta', array("public"=>1) );
	// use wp's built in wpmu_validate_blog_signup validation for all new site vars
	// also, use a test on  a known valid name/title to filter out any validation errors added by other plugins via the wpmu_validate_blog_signup filter
	$baseline_validation = wpmu_validate_blog_signup( 'nsclonervalidationtest', 'NS Cloner Validation Test' );
	$site_data = wpmu_validate_blog_signup( $site_name, $site_title, $user );
	$site_errors = array_diff( $baseline_validation['errors']->get_error_messages(), $site_data['errors']->get_error_messages() );
	if( !empty( $site_errors ) && false ){
		ns_log_write( array("Error creating site with name '$site_name' and title '$site_title'. One or more problems errors detected by WP:",$site_errors), $logfile );
		return false;
	}
	$site_id = wpmu_create_blog( $site_data["domain"], $site_data["path"], $site_title, $site_data["user"]->ID , $site_meta, get_current_site()->id );
	if( !is_wp_error( $site_id ) ) {
		ns_log_write( "New site with name '$site_name' and title '$site_title' (".get_site_url($site_id).") successfully created!", $logfile );
		return $site_id;
	}
	else {
		ns_log_write( "Error creating site with domain '$tmp_site_domain' and path '$tmp_site_path' - ".$site_id->get_error_message(), $logfile );
		return false;
	}
} 

/**
 * Create / Add users
 */
function ns_wp_add_user( $target_id, $useremail, $username, $userpass = '', $userrole = 'administrator', $logfile = false ) {
	ns_log_write( "ENTER ns_wp_add_user - target_id:$target_id, useremail:$useremail, username:$username, userrole:$userrole", $logfile );
	$useremail = stripslashes($useremail);
	$username = stripslashes($username);
	$userpass = stripslashes($userpass);
	$user_by_email = get_user_by( 'email', $useremail );
	$user_by_username = get_user_by( 'username', $username );	
	// check for existing user by email
	if( !empty($user_by_email) ) {
		$user_id = $user_by_email->ID;
		ns_log_write( "Found user with email '$useremail' (id=$user_id)", $logfile );
	}
	// check for existing user by username
	elseif( !empty($user_by_username) ) {
		$user_id = $user_by_username->ID;
		ns_log_write( "Found user with username '$username' (id=$user_id)", $logfile );
	}
	// no existing user with this email or username was found so create new one
	else {
		if( empty($userpass) || $userpass == strtolower( 'null' ) ) {
			$userpass = wp_generate_password();
		}
		$user_id = wpmu_create_user( $username, $userpass, $useremail );
		if( $user_id != false ){
			ns_log_write( "Created new user '$username' with email '$useremail'", $logfile );
		}
		else{
			ns_log_write( "Failed creating user '$username' with email '$useremail' - that username or email is probably already taken for a different user.", $logfile );
		}
	}
	// we now have a user id (or should) - give them privileges on this blog
	if( !empty($target_id) && !empty($user_id) && !empty($userrole) ){
		$result = add_user_to_blog( $target_id, $user_id, $userrole );
		if( $result === true ){
			ns_log_write( "Successfully added user with id $user_id to blog $target_id", $logfile );
		}
		else{
			$error_message = $result->get_error_message();
			ns_log_write( "Failed adding user to blog. WP error: $error_message", $logfile );
		}
		return $result;
	}
	else{
		$error_message = "Target id, user id, or user role were empty";
		ns_log_write( "Failed adding user to blog. $error_message", $logfile );
		return new WP_Error( false, $error_message );
	}
}


?>