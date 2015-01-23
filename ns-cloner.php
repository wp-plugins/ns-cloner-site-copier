<?php
/*
Plugin Name: NS Cloner - Site Copier
Plugin URI: http://neversettle.it
Description: All new V3 of the amazing time saving Never Settle Cloner! NS Cloner creates a new site as an exact clone / duplicate / copy of an existing site with theme and all plugins and settings intact in just a few steps. Check out the add-ons for additional powerful features!
Author: Never Settle
Version: 3.0.4
Network: true
Text Domain: ns-cloner
Author URI: http://neversettle.it
License: GPLv2 or later
*/
/*
Copyright 2012-2014 Never Settle (email : dev@neversettle.it)

This program is free software; you can redistribute it and/or 
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* 
This plugin uses code from db_backup (Alain Wolf, Zurich - Switzerland, GPLv2) 
rewritten by Andrew Lundquist (neversettle.it) to take the database backup 
script generation and automate the cloning process from scripts into queries
Original db_backup website: http://restkultur.ch/personal/wolf/scripts/db_backup/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

// load constants and libraries
define( 'NS_CLONER_V3_ADDON_FEED', 'http://neversettle.it/feed/?post_type=product&product_cat=ns-cloner-add-ons');
define( 'NS_CLONER_V3_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NS_CLONER_V3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NS_CLONER_LOG_FILE', NS_CLONER_V3_PLUGIN_DIR . 'logs/ns-cloner-summary.log' );
define( 'NS_CLONER_LOG_FILE_DETAILED', NS_CLONER_V3_PLUGIN_DIR . 'logs/ns-cloner-' . date("Ymd-His", time()) . '.html' );
define( 'NS_CLONER_LOG_FILE_URL', NS_CLONER_V3_PLUGIN_URL . 'logs/ns-cloner-summary.log' );
define( 'NS_CLONER_LOG_FILE_DETAILED_URL', NS_CLONER_V3_PLUGIN_URL . 'logs/ns-cloner-' . date("Ymd-His", time()) . '.html' );
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/kint/Kint.class.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/ns-utils.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/ns-log-utils.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/ns-file-utils.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/ns-sql-utils.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/lib/ns-wp-utils.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/ns-cloner-addon-base.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/ns-cloner-section-base.php');
require_once(NS_CLONER_V3_PLUGIN_DIR.'/ns-sidebar/ns-sidebar.php');

// load after plugins_loaded so that textdomain/translation works
add_action( 'plugins_loaded', 'ns_cloner_instantiate' );
function ns_cloner_instantiate($plugins){
	global $ns_cloner;
	$ns_cloner = new ns_cloner();
}

class ns_cloner {

	/**
	 * Class Globals
	 */
	var $version = '3.0.3.1';
	var $menu_slug = 'ns-cloner';
	var $capability = 'manage_network_options';
	var $global_tables = array(
		'blogs',				//exclude default multisite tables,
		'blog_versions',
		'registration_log',
		'signups',
		'site',
		'sitecategories',
		'sitemeta', 			
		'usermeta','users', 	//user tables (user copying handled elsewhere),
		'domain_mapping.*',     //domain mapping tables,
		'3wp_broadcast_.*',		//3wp broadcast tables,
		'bp_.*', 				//buddypress tables
	);
	var $addons = array();
	var $clone_modes = array();
	var $pipeline_steps = array();
	var $current_action;
	var $current_clone_mode;
	var $request = array();
	var $report = array();
	var $start_time;
	var $end_time;
	var $source_db;
	var $target_db;
	var $source_id;
	var $target_id;
	var $source_prefix;
	var $target_prefix;
	var $source_subd;
	var $target_subd;
	var $source_title;
	var $target_title;
	var $source_upload_dir;
	var $target_upload_dir;
	var $source_upload_dir_relative;
	var $target_upload_dir_relative;
	var $source_upload_url;
	var $target_upload_url;
	var $source_upload_url_relative;
	var $target_upload_url_relative;
	var $source_url;
	var $target_url;

	function __construct() {
		// activation hook for making sure this is multisite installed
		register_activation_hook( __FILE__, array($this,'activate') );
		// add hook for addons that need to set stuff before the core loads
		do_action( 'ns_cloner_before_construct', $this );
		// setup languages
		load_plugin_textdomain( 'ns-cloner', false, NS_CLONER_V3_PLUGIN_DIR.'languages' ); 
		// add functionality handler for admin 		
		add_action( 'admin_init', array( $this, 'admin_init' ) );		
		// add css for admin		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets') );		
		// add admin menus
		add_action( 'network_admin_menu', array( $this, 'admin_menu_pages' ) );
		// add quick-clone link
		add_action( 'manage_sites_action_links', array( $this, 'admin_quick_clone_link'), 10, 3 );
		// allow additional mode registration
		$this->clone_modes = apply_filters( 'ns_cloner_clone_modes', array(
			'core' => array(
				'title' => __( 'Normal Clone', 'ns-cloner' ),
				'button_text' => __( 'Clone', 'ns-cloner' ),
				'description' => __( 'Take an existing site and create a brand new copy of it at another url.', 'ns-cloner' ),
				'report_message' => __(	'Clone complete!' )
			)
		));
		// set up request vars
		$this->set_up_request();
		// load core sections
		$this->load_section( 'select-source' );
		$this->load_section( 'create-target' );
		$this->load_section( 'copy-tables-cta' );
		$this->load_section( 'copy-users-cta' );
		$this->load_section( 'copy-files-cta' );
		$this->load_section( 'search-replace-cta' );
		$this->load_section( 'additional-settings' );
		// register core pipeline steps
		if( $this->current_clone_mode=='core' ){
			add_filter( 'ns_cloner_pipeline_steps', array($this,'register_create_site_step'), 100 );
			add_filter( 'ns_cloner_pipeline_steps', array($this,'register_clone_tables_step'), 200 );		
			add_filter( 'ns_cloner_pipeline_steps', array($this,'register_copy_files_step'), 300 );
		}
		// add main hook for addon registration
		do_action( 'ns_cloner_construct', $this );
		// set up ajax for searching sites
		add_action( 'wp_ajax_ns_cloner_search_sites', array($this,'ajax_search_sites') );		
	}

	function activate( $network_wide ){
		if( !$network_wide ){
			ns_add_admin_notice( __("Sorry, the NS Cloner is a multisite only plugin. It won't work on a single site like this. Read more <a href='http://codex.wordpress.org/Create_A_Network' target='_blank'>here</a>"), "error" );
		}
	}
	 
	/**********************************
	 * Admin
	 */
	 
	function admin_init(){
		// if we are on a cloner admin page
	 	if( ns_is_admin_page($this->menu_slug) || ns_is_admin_subpage($this->menu_slug) ){
	 		// check that logs are writeable
		 	ns_log_check( NS_CLONER_LOG_FILE );
			ns_log_check( NS_CLONER_LOG_FILE_DETAILED, false );
			// run action for addons to hook on admin page whether or not a clone process is being triggered
			// (ns_cloner_before_everything below will only trigger if an action is being run)
			do_action( 'ns_cloner_admin_init' );
		}
		// run cloner if on the core cloner page and an action has been submitted and user is allowed to clone
		if( ns_is_admin_page($this->menu_slug) && !empty($this->request['action']) && $this->check_permissions() ){
			$this->dlog_header();
			$this->process_init();
		}
	 }

	function admin_assets(){
		if( ns_is_admin_page($this->menu_slug) || ns_is_admin_subpage($this->menu_slug) ){
			wp_enqueue_style( 'ns-cloner', NS_CLONER_V3_PLUGIN_URL.'css/ns-cloner-style.css', array(), $this->version );
			wp_enqueue_script( 'ns-cloner', NS_CLONER_V3_PLUGIN_URL.'js/ns-cloner-script.js', array('jquery','jquery-ui-autocomplete'), $this->version );
			wp_localize_script(
				'ns-cloner',
				'ns_cloner',
				array(
					'nonce' => wp_create_nonce('ns_cloner'),
					'ajaxurl' => admin_url('/admin-ajax.php'),
					'loadingimg' => NS_CLONER_V3_PLUGIN_URL . 'images/loading.gif'
				)
			);
		}
	}
	
	function admin_quick_clone_link( $action_links, $blog_id, $blog_name ){
		global $domain;
		$site_domain = str_replace('www.','',$domain);
		$site_base = get_current_site()->path;
		// determine the new clone's name and title
		// this has to make sure there will be no conflicts with existing sites so keep bumping up the copy number until no existing conflicting sites are found
		$duplicate_count = 1;
		do { 
			$duplicate_count++;
			$target_name = preg_replace('/\..+$/','',$blog_name)."-$duplicate_count";
			$target_domain = is_subdomain_install()? $target_name.'.'.$site_domain : $site_domain;
			$target_path = is_subdomain_install()? $site_base : $site_base.$target_name.'/';
		} while( domain_exists($target_domain,$target_path) );
		$target_title = urlencode( get_blog_option($blog_id,'blogname')." (Copy $duplicate_count)" );
		// add the link to the site action links
		$link = $this->build_url( array(
			"action" => "process",
			"clone_mode" => "core",
			"source_id" => $blog_id,
			"target_name" => $target_name,
			"target_title" => $target_title,
			"disable_addons" => true,
			"clone_nonce" => wp_create_nonce("ns_cloner")
		));
		$action_links['clone'] = '<span class="clone"><a href="'.$link.'" target="_blank">Clone</a></span>';
		return $action_links;
	}

	function admin_menu_pages(){
		// Add main top level page menu below Sites	
		add_menu_page(
			__( 'NS Cloner V3', 'ns-cloner' ), 
			__( 'NS Cloner V3', 'ns-cloner' ), 
			$this->capability, 
			$this->menu_slug, 
			array( $this, 'admin_render_main_page' ),
			plugin_dir_url( __FILE__ ).'images/cloner-admin-icon.png', 
			6 
		);		
		// Add Add-ons listing submenu 
		add_submenu_page(
			$this->menu_slug,
			__( 'Add-ons', 'ns-cloner' ),
			__( 'Add-ons', 'ns-cloner' ),
			$this->capability,
			'ns-cloner-addons',
			array( $this, 'admin_render_addons_page' )
		);
		
		// TODO: Provide an action or filter or other mechanism for add-ons to more easily add their own menu items
		// TODO: Update the Registration Templates add-on to use it
	}
	
	function admin_render_main_page(){
		self::render('main');
	}
	
	function admin_render_addons_page(){
		self::render('addons');
	}

	/*********************************
	 * Pipeline
	 */
	
	 // All setup/validation should take place here
	 function process_init(){
		
		// run startup hook
		do_action( 'ns_cloner_before_everything', $this );
		$this->dlog( 'AFTER ACTION ns_cloner_before_everything' );
		
		// perform validation 
		$this->do_validation();
		
		// run process if no errors
		$this->process();
		
		// run shutdown hook
		do_action( 'ns_cloner_after_everything', $this );
		$this->dlog( 'AFTER ACTION ns_cloner_after_everything' );
		
		// track time spent on whole clone operation
		$this->end_time = microtime(true);
		$this->report[ __('Total process time','ns-cloner') ] = number_format( $this->end_time-$this->start_time, 4 ) .' '. __('seconds','ns-cloner');
		$this->dlog( 'END TIME: '.$this->end_time );
		$this->dlog( 'Entire cloning process took: <strong>'.number_format( $this->end_time-$this->start_time, 4 ).'</strong> seconds');
		$this->dlog_footer();
		
		// summary log
		$this->log( $this->target_url." cloned in ".number_format( $this->end_time-$this->start_time, 4 )." seconds" );
		
		// save vars for report - link to log file, clone mode success message 
		$this->report[ __('Log file','ns-cloner') ] = NS_CLONER_LOG_FILE_DETAILED_URL;
		$this->report['_message'] = $this->clone_modes[ $this->current_clone_mode ]['report_message'];
		
		// add warning to report if this new site ended up with the same upload dir as another site
		// figure out shared paths by getting all upload paths and then filtering to only the ones that match this one (then see if there are more than one)
		$sites_with_same_upload_dir = array_filter( ns_get_multisite_upload_paths(), create_function('$dir','return $dir==\''.$this->target_upload_dir.'\';') );
		if( sizeof($sites_with_same_upload_dir) > 1 ){
			$this->report['_warning'] .= sprintf(
				__('WARNING! The cloned site has the same upload path (%s) as site id(s) %s. If you leave the upload_path options on both sites as they are, <strong>deleting either site will delete all of the other\'s uploads.</strong>','ns-cloner'),
				$this->target_upload_dir,
				join( ',', array_keys($sites_with_same_upload_dir) )
			);
		}
						
		// redirect back and show report (unless another plugin disables by turning on invisible mode like for calling cloner programatically)
		if( apply_filters( 'ns_cloner_do_reporting', true, $this ) ){
			$report_vars = apply_filters( 'ns_cloner_report_vars', $this->report, $this );
			set_site_transient( 'ns_cloner_report_'.get_current_user_id(), $report_vars );
			wp_redirect( apply_filters( 'ns_cloner_success_redirect', admin_url('/network/admin.php?page='.$this->menu_slug), $this ) );
			exit;
		}

	 }
	
	 // All actual operations should take place here
	 function process(){
	 	$this->dlog( 'ENTER ns_cloner::process' );

		do_action( "ns_cloner_before_{$this->current_action}" );
		$this->dlog( "AFTER ACTION ns_cloner_before_{$this->current_action}" );
		
		switch( $this->current_action ) {
			
			// main core process action
			case 'process':

				// define pipeline steps to go through
				$this->pipeline_steps = apply_filters( 'ns_cloner_pipeline_steps', $this->pipeline_steps, $this );
				$this->dlog( array("PIPELINE STEPS:",array_keys($this->pipeline_steps)) );
				
				foreach( $this->pipeline_steps as $step=>$function ){					
					do_action( "ns_cloner_before_$step" );
					$this->dlog( "AFTER ACTION ns_cloner_before_$step" );
					if( is_callable($function) ){
						call_user_func($function,$this);
					}
					else{
						$this->dlog( array("Function for this step was not callable:",$function) );
					}
					do_action( "ns_cloner_after_$step" );
					$this->dlog( "AFTER ACTION ns_cloner_after_$step" );					
				}
				
				break;
			
			// ajax validation action
			case 'ajax_validate':
			
				header('Content-Type: application/json');
				echo json_encode( array("status"=>"success") );
				exit;
				break;
			
			// all other extendable actions
			default :
				
				do_action("ns_cloner_do_{$this->current_action}");				
				break;

		}

		do_action("ns_cloner_after_{$this->current_action}");
		$this->dlog( "AFTER ACTION ns_cloner_after_{$this->current_action}" );
	 }

	function create_site(){
		$this->dlog( 'ENTER ns_cloner::create_site' );
		$target_name = $this->request['target_name'];
		$target_title = $this->request['target_title'];
		$target_id = ns_wp_create_site( $target_name, $target_title, NS_CLONER_LOG_FILE_DETAILED );
		// handle unsuccessful creation
		if( $target_id == false ){
			wp_die( __('Unable to create new site for cloning operation. Check the cloner logs for details.','ns-cloner') );
		}
		// site was successful, so define all vars based on it
		else{
			$this->set_up_vars( $this->request['source_id'], $target_id );
		}
	}
	
	function clone_tables(){		
		$this->dlog( 'ENTER ns_cloner::clone_tables' );
		
		// Setup replacements for standard url/name substitution + character encoding issues
		$search	= array(
			$this->source_upload_dir_relative,
			$this->source_upload_url_relative,
			$this->source_subd,
			$this->source_prefix."user_roles",
			$this->source_title
		);
		$replace = array(
			$this->target_upload_dir_relative,
			$this->target_upload_url_relative,
			$this->target_subd,
			$this->target_prefix."user_roles",
			$this->target_title
		);
		$search = apply_filters( 'ns_cloner_search_items', $search, $this);
		$replace = apply_filters( 'ns_cloner_replace_items', $replace, $this);
		$regex_search = apply_filters( 'ns_cloner_regex_search_items', array(), $this );
		$regex_replace = apply_filters( 'ns_cloner_regex_replace_items', array(), $this );
		$this->dlog( array("String search targets:",$search) );
		$this->dlog( array("String search replacements:",$replace) );
		$this->dlog( array("Regex search targets:",$regex_search) );
		$this->dlog( array("Regex search replacements:",$regex_replace) );
		
		// Sort and filter replacements to intelligently avoid compounding replacement issues - more details in function comments in lib/ns-utils.php 
		if( apply_filters('ns_do_search_replace_validation',true) ){
			ns_set_search_replace_sequence( $search, $replace, $regex_search, $regex_replace, NS_CLONER_LOG_FILE_DETAILED );
			$search = apply_filters( 'ns_cloner_search_items_after_sequence', $search, $this);
			$replace = apply_filters( 'ns_cloner_replace_items_after_sequence', $replace, $this);
			$regex_search = apply_filters( 'ns_cloner_regex_search_items_after_sequence',$regex_search, $this );
			$regex_replace = apply_filters( 'ns_cloner_regex_replace_items_after_sequence', $regex_replace, $this );
			$this->dlog( array("String search targets after sequence:",$search) );
			$this->dlog( array("String search replacements after sequence:",$replace) );
			$this->dlog( array("Regex search targets after sequence:",$regex_search) );
			$this->dlog( array("Regex search replacements after sequence:",$regex_replace) );
		}
		
		// Fetch source tables and start cloning
		$tables = $this->get_site_tables( $this->source_db, $this->source_prefix );			
		$count_tables_cloned = $count_replacements_made = 0;
						
		if ( is_array($tables) && count($tables) > 0 ) {
			foreach( $tables as $source_table ){
				
				$count_tables_cloned++;
				// if it's a non-prefixed table (root/main), prepend the prefix on, otherwise do replacement
				if( strpos($source_table,$this->source_prefix)===false ){
					$target_table = $this->target_prefix . $source_table;
				}
				else{
					$target_table = str_replace($this->source_prefix, $this->target_prefix, $source_table);
				}
				$quoted_source_table = ns_sql_backquote($source_table);
				$quoted_target_table = ns_sql_backquote($target_table);
				
				// Log which table this is (and don't copy a table to itself if for some reason prefix didn't change)
				$this->dlog_break();
				if ( $source_table == $target_table ) {
					$this->dlog( "Source table: <b>{$source_table}</b> and Target table: <b>{$target_table} are the same! SKIPPING!!!</b>" );
					continue;
				}
				else{
					$this->dlog( "Cloning source table: <b>{$source_table}</b> to Target table: <b>{$target_table}</b>" );
				}
				$this->dlog_break();
				
				// Drop the target table if it already exists to avoid conflicts
				if( apply_filters('ns_cloner_do_drop_target_table',true,$target_table,$this) ){
					$query = "DROP TABLE IF EXISTS ".$quoted_target_table;
					$this->target_db->query( $query );
					$this->handle_any_db_errors( $this->target_db, $query );
				}
					
				// Get table structure
				$query = "SHOW CREATE TABLE ".$quoted_source_table;
				$structure = $this->source_db->get_var( $query, 1, 0 );
				$this->handle_any_db_errors( $this->source_db, $query );
					
				// Create cloned table structure
				$query = str_replace( $quoted_source_table, $quoted_target_table, $structure );
				$this->target_db->query( apply_filters( 'ns_cloner_create_table_query', $query, $this ) );
				$this->handle_any_db_errors( $this->target_db, $query );
				
				// Get table contents
				$query = "SELECT * FROM ".$quoted_source_table;
				$contents = $this->source_db->get_results( $query, ARRAY_A );
				$this->handle_any_db_errors( $this->source_db, $query );
				$this->dlog("Number of rows: ".count($contents));
				
				foreach( $contents as $row ){
					// skip any junk rows which shouldn't/needn't be copied
					if(
						( isset($row['option_name']) && preg_match('/(_transient_rss_|_transient_feed_)/',$row['option_name']) ) ||
						( isset($row['meta_key']) && preg_match('/(_edit_lock|_edit_last)/',$row['meta_key']) ) || 
						( !apply_filters('ns_cloner_do_copy_row',true,$row,$source_table) )
					){
						continue;
					}
					// perform replacements
					foreach($row as $field=>$value){
						$row_count_replacements_made = ns_recursive_search_replace( $value, $search, $replace, $regex_search, $regex_replace, isset($this->request['case_sensitive']) );
						$row[$field] = apply_filters( 'ns_cloner_field_value', $value, $field, $row, $this );
						$count_replacements_made += $row_count_replacements_made;
					}
					// actually copy row
					$this->target_db->insert( $target_table, $row );
					$this->handle_any_db_errors( $this->target_db, "INSERT INTO $target_table via wpdb --> ".print_r($row,true) );
				} // end rows loop
				
			} // end tables loop

			$this->report[ __('Tables cloned','ns-cloner') ] = $count_tables_cloned;
			$this->report[ __('Replacements made','ns-cloner') ] = $count_replacements_made;
			$this->dlog( 'Cloned: <b>' .$count_tables_cloned . '</b> tables!' );
			$this->dlog( 'Replaced: <b>' .$count_replacements_made. '</b> occurences of search strings!' );
			
		}

		else {
			$this->dlog( 'No tables found for cloning' );
		}
	}

	function copy_files(){
		$this->dlog( 'ENTER ns_cloner::copy_files' );
		$num_files = ns_recursive_dir_copy( $this->source_upload_dir, $this->target_upload_dir, 0 );
		$this->report[ __('Files/directories copied','ns-cloner') ] = $num_files;
		$this->dlog ('Copied: <b>' . $num_files . '</b> folders and files!');
		$this->dlog ('From: <b>' . $this->source_upload_dir . '</b>');
		$this->dlog ('To: <b>' . $this->target_upload_dir . '</b>');
	}

	/*****************************
	 * Pipeline Helpers
	 */
		 
	// Helpers for registering core cloner steps - can be used by addons to include core steps in new modes
	function register_create_site_step( $steps ){
		$steps["create_site"] = array($this,"create_site");
		return $steps;
	}	
	function register_clone_tables_step( $steps ){
		$steps["clone_tables"] = array($this,"clone_tables");
		return $steps;
	}	
	function register_copy_files_step( $steps ){
		$steps["copy_files"] = array($this,"copy_files");
		return $steps;
	}
	
	// Check whether the current user can run a clone operation + whether nonce is valid, then optionally die or just return false
	function check_permissions( $die=true ){
		$required_capability = apply_filters( "ns_cloner_{$this->current_action}_required_capability", $this->capability, $this );
		$can_do = current_user_can( $required_capability ); 
		if( !$can_do ) {
			if( $die ){
				wp_die( __('You don\'t have sufficient permissions to create site clones.','ns-cloner') );
				exit;
			}
			else {
				return false;
			}
		}
		$valid_nonce = wp_verify_nonce( $this->request['clone_nonce'], 'ns_cloner' );
		if( !$valid_nonce ){
			if( $die ){
				wp_die( __('Invalid nonce.','ns-cloner') );
				exit;
			}
			else {
				return false;
			}
		}
		do_action('ns_cloner_after_capable');
		return true;
	}
	
	// Perform validation and end program flow if it fails (either redirect with admin notice or return json depending on ajax param)
	function do_validation(){
		$validation_errors = apply_filters( "ns_cloner_valid_errors", array(), $this );
		if( !empty($validation_errors) ){
			$this->dlog( array("VALIDATION ERRORS:",$validation_errors) );
			if( $this->current_action == 'ajax_validate' ){
				header('Content-Type: application/json');
				echo json_encode( array("status"=>"error","messages"=>$validation_errors) );
				exit;
			}
			else{
				foreach( $validation_errors as $error ){
					ns_add_admin_notice( $error["message"], "error", $this->menu_slug, true );
				}
				wp_redirect(wp_get_referer());
				exit;
			}
		}
		do_action('ns_cloner_after_valid');
	}
	
	// Define filtered request vars - this comes earlier than specific operation vars 
	function set_up_request( $request = null ){		
		// allow filtering of $_REQUEST vars + set up class vars from request
		$this->request = apply_filters( 'ns_cloner_request_vars', is_null($request)? array_merge($_GET,$_POST) : $request );
		// set action - for all cloning operations will be "process"
		// this is for flexibility if we eventually want to run admin actions 
		// further outside of the normal pipeline than can be controlled by modes
		if( isset($this->request['action']) && !empty($this->request['action']) ){
			$this->current_action = $this->request['action'];
		}
		// set clone mode - default is "core" and can be extended by addons
		// used for smaller adjustments that stick to general cloning pipeline
		// but just add/remove/reorder steps
		if( isset($this->request['clone_mode']) && !empty($this->request['clone_mode']) ){
			$this->current_clone_mode = $this->request['clone_mode'];
		}		
	}

	// Define all operation specific variables we'll need for core clone operation 
	function set_up_vars( $source_id, $target_id, $vars=array('id','prefix','subd','title','upload_dir','upload_url','url') ){		
		$this->set_up_source_vars( $source_id, $vars );
		$this->set_up_target_vars( $target_id, $vars );
	}

	function set_up_source_vars( $source_id, $vars=array('id','prefix','subd','title','upload_dir','upload_url','url') ){
		// db
		if( is_null($this->source_db) ){
			global $wpdb;
			$default_db_creds = array( 'host'=>DB_HOST, 'name'=>DB_NAME, 'user'=>DB_USER, 'password'=>DB_PASSWORD );
			$source_db_creds = apply_filters( 'ns_cloner_source_db_credentials', $default_db_creds, $this );
			if( $source_db_creds !== $default_db_creds ){
				$this->source_db = @( new ns_wpdb( $source_db_creds['user'], $source_db_creds['password'], $source_db_creds['name'], $source_db_creds['host'] ) );
				if( !empty($this->source_db->last_error) ){
					$this->dlog("Could not connect to and select the source database. Error:".$this->source_db->last_error);
					if( is_network_admin() ){
						ns_add_admin_notice( __("Could not connect to and select the source database","ns-cloner"), "error", $this->menu_slug, true );
						wp_redirect(wp_get_referer());
						exit;
					}
				}
			}
			else{
				$this->source_db = $wpdb;	
			}
		}
		//ids
		if( in_array('id',$vars) ){
			$this->source_id = $this->report[ __("Old Site ID","ns-cloner") ] = $source_id;
			$this->dlog( "Setting source id: " . $this->source_id );
		}
		//db prefixes
		if( in_array('prefix',$vars) ){
			$this->source_prefix = $source_id==1? $this->source_db->base_prefix : $this->source_db->base_prefix . $source_id . '_';
			$this->dlog( "Setting source prefix: " . $this->source_prefix );
		}		
		//subdomains/dirs
		if( in_array('subd',$vars) ){
			$this->source_subd = untrailingslashit( get_blog_details($this->source_id)->domain . get_blog_details($this->source_id)->path );
			$this->dlog( "Setting source subdomain/subdirectory: " . $this->source_subd );
		}		
		//titles
		if( in_array('title',$vars) ){
			$this->source_title = get_blog_details($this->source_id)->blogname;
			$this->dlog( "Setting source site title: " . $this->source_title );
		}	
		//upload dirs
		if( in_array('upload_dir',$vars) ){
			$this->source_upload_dir = ns_get_upload_dir( $this->source_id, NS_CLONER_LOG_FILE_DETAILED );
			$this->source_upload_dir_relative = str_replace( ns_norm_winpath(ABSPATH), '', $this->source_upload_dir );
			$this->dlog( "Setting source full upload dir path: " . $this->source_upload_dir . " and shorter relative path: " .$this->source_upload_dir_relative );
		}		
		//upload urls
		if( in_array('upload_url',$vars) ){
			$this->source_upload_url = ns_get_upload_url( $this->source_id, NS_CLONER_LOG_FILE_DETAILED );
			$this->source_upload_url_relative = str_replace( get_site_url().'/', '', $this->source_upload_url );
			$this->dlog( "Setting source full upload url: " . $this->source_upload_url . " and shorter relative url: " .$this->source_upload_url_relative );
		}	
		//urls
		if( in_array('url',$vars) ){
			$this->source_url = $this->report[ __('Old Site','ns-cloner') ] = get_blog_details( $this->source_id, true )->siteurl;
			$this->dlog( "Setting source url " . $this->source_url );
		}
	}
	
	function set_up_target_vars( $target_id, $vars=array('id','prefix','subd','title','upload_dir','upload_url','url') ){
		//db
		if( is_null($this->target_db) ){
			global $wpdb;
			$target_db_creds = apply_filters( 'ns_cloner_target_db_credentials', $default_db_creds, $this );		
			if( $target_db_creds !== $default_db_creds ){
				$this->target_db = @( new ns_wpdb( $target_db_creds['user'], $target_db_creds['password'], $target_db_creds['name'], $target_db_creds['host'] ) );
				if( !empty($this->target_db->last_error) ){
					$this->dlog("Could not connect to and select the target database. Error:".$this->target_db->last_error);
					if( is_network_admin() ){
						ns_add_admin_notice( __("Could not connect to and select the target database","ns-cloner"), "error", $this->menu_slug, true );
						wp_redirect(wp_get_referer());
						exit;
					}
								
				}
			}
			else{
				$this->target_db = $wpdb;		
			}
		}
		//ids
		if( in_array('id',$vars) ){
			$this->target_id = $this->report[ __("New Site ID","ns-cloner") ] = $target_id;
			$this->dlog( "Setting target id: " . $this->target_id );
		}
		//db prefixes
		if( in_array('prefix',$vars) ){
			$this->target_prefix = $target_id==1? $this->target_db->base_prefix : $this->target_db->base_prefix . $target_id . '_';
			$this->dlog( "Setting target prefix: " . $this->target_prefix );
		}
		//subdomains/dirs
		if( in_array('subd',$vars) ){
			$this->target_subd = untrailingslashit( get_blog_details($this->target_id)->domain . get_blog_details($this->target_id)->path );
			$this->dlog( "Setting target subdomain/subdirectory: " . $this->target_subd );
		}	
		//titles
		if( in_array('title',$vars) ){
			$this->target_title = get_blog_details($this->target_id)->blogname;
			$this->dlog( "Setting target site title: " . $this->target_title );
		}
		//upload dirs
		if( in_array('upload_dir',$vars) ){
			$this->target_upload_dir = ns_get_upload_dir( $this->target_id, NS_CLONER_LOG_FILE_DETAILED );
			$this->target_upload_dir_relative = str_replace( ns_norm_winpath(ABSPATH), '', $this->target_upload_dir );
			$this->dlog( "Setting target full upload dir path: " . $this->target_upload_dir . " and shorter relative path: " .$this->target_upload_dir_relative );
		}
		//upload urls
		if( in_array('upload_url',$vars) ){
			$this->target_upload_url = ns_get_upload_url( $this->target_id, NS_CLONER_LOG_FILE_DETAILED );
			$this->target_upload_url_relative = str_replace( get_site_url().'/', '', $this->target_upload_url );
			$this->dlog( "Setting target full upload url: " . $this->target_upload_url . " and shorter relative url: " .$this->target_upload_url_relative );
		}
		//urls
		if( in_array('url',$vars) ){
			$this->target_url = $this->report[ __('New Site','ns-cloner') ] = get_blog_details( $this->target_id, true )->siteurl;
			$this->dlog( "Setting target url " . $this->target_url );
		}
	}

	function ajax_search_sites(){
		global $wpdb;
		if( $this->check_permissions(false) ){
			header('Content-type:application/json');
			$matching_sites = array();
			$search_value = esc_sql( $_REQUEST['term'] );
			$search_column = is_subdomain_install()? 'domain' : 'path';
			$results = $wpdb->get_results("SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE $search_column LIKE '%$search_value%'");
			foreach( $results as $result ){
				$details = get_blog_details($result->blog_id);
				array_push( $matching_sites, array(
					'value' => $details->blog_id,
					'label' => "$details->blogname ($details->siteurl)"
				));
			}
			echo json_encode($matching_sites);
			exit;
		}
	}

	/*****************************************
	 * Utility
	 */
	 
	public static function render( $template, $plugin_dir = NS_CLONER_V3_PLUGIN_DIR ){
		global $ns_cloner;
		// Removed filter approach as I think that would affect other template renders once there's a 
		// filter on ns_cloner_template... otherwise you'd have to add_filter and remove_filter every time.
		// Just passing in an optional plugin_dir makes a filter unnecessary
		$render_template = $plugin_dir.'/templates/ns-template-'.$template.'.php'; 
		do_action( "ns_cloner_before_render", $template );
		include_once( $render_template );
		do_action( "ns_cloner_after_render", $template );
	}
	 
	public function log( $message ){
		ns_log_write( $message, NS_CLONER_LOG_FILE );
	}
	
	public function dlog( $message, $debug_only=false ){
		$is_ajax = $this->current_action=="ajax_validate" || (defined('DOING_AJAX') && DOING_AJAX==true);
		$is_extra_debug_on = isset($this->request["debug"]) && $this->request["debug"]==true;
		if( ($debug_only==true || $is_ajax) && !$is_extra_debug_on ) return;
		ns_log_write( $message, NS_CLONER_LOG_FILE_DETAILED );
	}
	
	public function dlog_break( $debug_only=false ){
		$is_ajax = $this->current_action=="ajax_validate" || (defined('DOING_AJAX') && DOING_AJAX==true);
		$is_extra_debug_on = isset($this->request["debug"]) && $this->request["debug"]==true;
		if( ($debug_only==true || $is_ajax) && !$is_extra_debug_on ) return;
		ns_log_section_break( NS_CLONER_LOG_FILE_DETAILED );
	}
	
	public function dlog_header(){
		$is_ajax = $this->current_action=="ajax_validate" || (defined('DOING_AJAX') && DOING_AJAX==true);
		$is_extra_debug_on = isset($this->request["debug"]) && $this->request["debug"]==true;
		if( $is_ajax && !$is_extra_debug_on ) return;
		ns_log_open( NS_CLONER_LOG_FILE_DETAILED );
		$this->dlog_break();
		ns_diag( NS_CLONER_LOG_FILE_DETAILED );
		$this->dlog_break();
		$this->start_time = microtime(true);
		$this->dlog( 'START TIME: '.$this->start_time.' ('.date('Y-m-d H:i:s').')' );
	 	$this->dlog( 'ENTER ns_cloner::process_init' );
		$this->dlog( 'RUNNING NS Cloner version: <strong>' . $this->version . '</strong>' );			
		$this->dlog( 'ADDONS: '.join(', ',array_map('get_class',$this->addons)) );
		$this->dlog( 'ACTION: '.$this->current_action );			
		$this->dlog( 'CLONING MODE: '.$this->current_clone_mode );
		$this->dlog( array('FILTERED REQUEST:',$this->request) );
		$this->dlog_break();
	}
	
	public function dlog_footer(){
		$is_ajax = $this->current_action=="ajax_validate" || (defined('DOING_AJAX') && DOING_AJAX==true);
		$is_extra_debug_on = isset($this->request["debug"]) && $this->request["debug"]==true;
		if( $is_ajax && !$is_extra_debug_on ) return;
		ns_log_close( NS_CLONER_LOG_FILE_DETAILED );
	}
	
	// Get an array of table names which should be copied from a source site
	public function get_site_tables( $db, $prefix, $filter=true ){
		//list of tables for root/main site
		if( $prefix==$db->base_prefix ){
			$all_tables = $db->get_col('SHOW TABLES');
			// define patterns for subsites (eg wp_2_...) and global tables (eg wp_blogs) which should not be copied
			$subsite_table_pattern = "/^$db->base_prefix\d+_/";
			$global_table_pattern = "/^$db->base_prefix(" .implode( '|', apply_filters('ns_cloner_global_tables',$this->global_tables) ). ")$/";
			$tables = array();
			foreach($all_tables as $table){
				if( !preg_match($global_table_pattern,$table) && !preg_match($subsite_table_pattern,$table) ){
					array_push($tables, $table);
				}
			}
		}
		//list of tables when cloning non-root
		else{
			// escape '_' characters otherwise they will be interpreted as wildcard single chars in LIKE statement
			$escaped_prefix = esc_sql(str_replace('_','\_',$prefix));
			$tables = $db->get_col("SHOW TABLES LIKE '{$escaped_prefix}%'");
		}
		//apply filter and return
		return $filter!=true? $tables : apply_filters( "ns_cloner_site_tables", $tables, $db, $prefix, $this );
	}
	
	// Log (and if operation can't go on, die) any database errors given a wpdb object after executing a query
	public function handle_any_db_errors( $connection, $query, $last_operation_fatal=true ){
		$this->dlog( $query, true );
		if( !empty($connection->last_error) ){
			$this->dlog( "SQL error: ". $connection->last_error );
			$this->dlog( "For Query: ". $query );
			if( $last_operation_fatal ){
				wp_die( sprintf( __("Uh-oh - there was an sql error: %s.","ns-cloner"), $connection->last_error ) );
			}		
		}
	}
	
	// Retrieve an array of urls for detail log files from X number of past days
	public static function get_recent_logs( $days=7 ){
		$recent_logs = array();
		$all_logs = (array) @scandir( NS_CLONER_V3_PLUGIN_DIR.'logs' );
		$requested_days_in_seconds = $days * 24 * 60 * 60;
		foreach( $all_logs as $log ){
			if( preg_match('/ns-cloner-(\d{8})-(\d{6})\.html/',$log,$date_matches) ){
				// check if it's in the requested time period
				$seconds_since_this_log = strtotime('now') - strtotime("$date_matches[1] $date_matches[2]");
				if( $seconds_since_this_log <= $days*24*60*60 ){
					$recent_logs[] = NS_CLONER_V3_PLUGIN_URL.'logs/'.$log;
				}
			}
		}
		return $recent_logs;
	}
	
	// Encode and return a url that will trigger a cloning operation by visiting
	public function build_url( $request ){
		return network_admin_url( "/admin.php?page=".$this->menu_slug."&".http_build_query($request) );
	}

	/********************************
	 * Addons
	 */

	 // Addons call this to register themselves with the core - not currently used but could be useful later
	 public function register_addon( &$addon_object ){
	 	if( !in_array( $addon_object, $this->addons ) ){
	 		array_push( $this->addons, $addon_object );
	 	}
	 }
	 
	// Addons (or core) call this to include and instantiate new sections, the building blocks of the cloner 
	// which may in turn add ui, validation, processing (new steps or hooking to actions in existing steps), reporting, etc
	public function load_section( $section, $path = NS_CLONER_V3_PLUGIN_DIR ){
		$section_with_underscores = str_replace('-','_',$section);
		$section_with_dashes = str_replace('_','-',$section);
		if( apply_filters("ns_cloner_do_load_section_{$section_with_underscores}",true,$this) ){
			$section_filename = "ns-cloner-section-{$section_with_dashes}.php";
			$section_classname = "ns_cloner_section_{$section_with_underscores}";
			$section_path = "{$path}sections/{$section_filename}";
			require_once($section_path);
			if( class_exists($section_classname) ){
				global $$section_classname;
				$$section_classname = new $section_classname( $this );
			}
		}
	}
	
	 // Addons call this to register a new mode in the dropdown (or override/replace an existing one)
	 // Slug and details should be equivalent to the key and value of the mode (see default core mode defined in this class)
	 // $external_sections_supported is an array of strings so that an addon can retroactively activate other sections from
	 // either the core or other plugins and define those to be applicable for the new mode
	 public function register_mode( $slug, $details, $external_sections_supported=array() ){
		// perform new registration
		$this->clone_modes[ $slug ] = $details; 
		// go back and add this mode to the supported modes list for any other specified sections from core / other addons
		foreach( $external_sections_supported as $section ){
			$section_with_underscores = str_replace('-','_',$section);
			$section_classname = "ns_cloner_section_{$section_with_underscores}";
			global $$section_classname;
			if( !empty($$section_classname) ){
				array_push( $$section_classname->modes_supported, $slug );
			}
		}
	 }
	 
	 // TODO - make this work so it is simpler for addons than having to set up their own filters
	 public function register_pipeline_step( $slug, $function, $priority ){
	 	add_filter( 'ns_cloner_pipeline_steps', create_function('$steps','$steps['.$slug.']='.var_export($function,true).'; return $steps;'), $priority);
	 }

}
