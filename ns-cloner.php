<?php
/*
Plugin Name: NS Cloner - Site Copier
Plugin URI: http://neversettle.it
Description: Save loads of time with the Never Settle Cloner! NS Cloner creates a new site as an exact clone / duplicate / copy of an existing site with theme and all plugins and settings intact in just a few steps. Check out NS Cloner Pro for additional features like cloning onto existing sites and advanced Search and Replace functionality.
Author: Never Settle
Version: 2.1.4
Network: true
Author URI: http://neversettle.it
License: GPLv2 or later
*/

/*
Copyright 2012 Never Settle (email : dev@neversettle.it)

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

/* Pro Version To Do:
	1. Make the blogs.dir copy optional
	2. Add serialized array safe, global search and replace from source to clone
	3. Add search and replace only mode for existing sites
	4. Add users to the new site at clone-time
	5. Clone onto pre-existing sites
	6. Add detailed debug info mode option
	7. Add configuration options and save defaults for all settings
	8. Add default settings and hook into new registration
*/

define( 'NS_CLONER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NS_CLONER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// GLOBALS

// Database Settings assigned from WordPress Globals in wp-config.php
$host = DB_HOST;        
$db   = DB_NAME;     
$usr  = DB_USER;		
$pwd  = DB_PASSWORD;  

// Multisite Mode; false = subdirs; from WordPress Globals in wp-config.php
$is_subdomain = SUBDOMAIN_INSTALL;

// Report about what was accomplished
$report = '';		
$count_tables_checked;
$count_items_checked;
$count_items_changed;	

class ns_cloner_free {

	/**
	 * Class Globals
	 */
	var $version = '2.1.4';
	var $log_file = '';
	var $log_file_url = '';
	var $detail_log_file = '';
	var $detail_log_file_url = '';
	var $banner_img = '';
	var $adopter_img = '';
	var $topmenu = '';
	var $capability = '';
	var $target_id = '';
	var $status = '';

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		global $wp_version;

		// set the paths for the images and logs 
		$this->banner_img = NS_CLONER_PLUGIN_URL . 'images/ns-cloner-banner.jpg';
		$this->adopter_img = NS_CLONER_PLUGIN_URL . 'images/ns-cloner-adopter.jpg';
		$this->log_file = NS_CLONER_PLUGIN_DIR . 'logs/ns-cloner.log';
		$this->log_file_url = NS_CLONER_PLUGIN_URL . 'logs/ns-cloner.log';
		$this->detail_log_file = NS_CLONER_PLUGIN_DIR . 'logs/ns-cloner-' . date("Ymd-His", time()) . '.html';
		$this->detail_log_file_url = NS_CLONER_PLUGIN_URL . 'logs/ns-cloner-' . date("Ymd-His", time()) . '.html';

		add_action( 'admin_notices', array( $this, 'check_logfile' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		
		// add css for admin		
		add_action( 'admin_enqueue_scripts', 'add_ns_styles' );

		// add admin menus		
		add_action( 'network_admin_menu', array( $this, 'plugin_pages' ) );
		$this->topmenu = 'sites.php';
		$this->capability = 'manage_network_options';
	}

	/**
	 * Create logfile or display error
	 */
	function check_logfile() {
		if( ! file_exists( $this->log_file ) ) {
			$handle = fopen( $this->log_file, 'w' ) or printf( __( '<div class="error"><p>Unable to create log file %s. Is its parent directory writable by the server?</p></div>', 'ns-cloner' ), $this->log_file );
			fclose( $handle );
		}
	}

	/**
	 * Add admin menu / page
	 */
	function plugin_pages() {
		add_submenu_page( $this->topmenu, __( 'NS Cloner', 'ns_cloner' ), __( 'NS Cloner', 'ns_cloner' ), $this->capability, 'ns-cloner', array( $this, 'page_main_output' ) );
	}
	
	/**
	 * Admin Interface
	 */
	function page_main_output() {
		global $wpdb, $wp_roles, $current_user, $current_site, $is_subdomain;
		
		if( !current_user_can( $this->capability ) ) {
			echo '<p>' . __( 'You do not have permissions to do that...', 'ns_cloner' ) . '</p>'; // If accessed properly, this message doesn't appear.
			return;
		}
		
		// ---------------------------------------------------------------------------------
		// Default Configuration settings -- These are set by Settings in Pro version
		// ---------------------------------------------------------------------------------

		$abort = false;
		$source_id = '2';

		if ( isset( $_GET['error'] ) )
			echo '<div id="errmessage" class="error"><p>' . stripslashes( urldecode( $_GET['errormsg'] ) ) . '</p></div>';
		
		if ( isset( $_GET['updated'] ) )
			echo '<div id="message" class="updated fade"><p>' . stripslashes( urldecode( $_GET['updatedmsg'] ) ) . '</p></div>';
		
		// debug
		//echo $_SERVER['REQUEST_URI'];

		// Main UI Page
		echo '<div class="wrap">';
		?>
			<!-- <h1 class="cloner-title">NS Cloner</h1> -->
			<img class="cloner-banner" alt="Never Settle Cloner Title Banner" src="<?php echo $this->banner_img ?>" />
			<form action="?page=ns-cloner&action=process" method="post" enctype="multipart/form-data">
				
				<!-- BEGIN Left Column -->
				<div class="col-left">
			
					<div class="before-clone">
						<?php if (!isset($_GET['updatedmsg']) && !isset($_GET['errormsg'])) { ?>
							<h2 class="cloner-step before-clone-title">Before you begin</h2>
							<p>If you haven't already, now is a great time to set up a "template" site exactly the way you want the new clone site to start out (theme, plugins, settings, etc.).</p>
						<?php } else { ?>
							<h2 class="cloner-step before-clone-title">Status</h2>
						<?php } ?>
					</div>
					<span class="colorRed">
						<h2 class="cloner-step">STEP 1: <span>Pick an existing site to clone</span></h2>
						<p><select id="source_id" name="source_id">
						<?php
							$blogs = $wpdb->get_results("SELECT * FROM $wpdb->blogs ORDER BY blog_id", ARRAY_A);
							foreach($blogs as $row){
								// blog id #1 not supported as it isn't a subsite and will cause domain name or folder issues
								if 	($row['blog_id'] !== '1') {
									$selected = '';			
									if ($row['blog_id'] == $source_id) { $selected = ' selected="selected"'; }
									if ($is_subdomain) {
										echo '<option value="' . $row['blog_id'] . '"' . $selected . '>' . $row['blog_id'] . ' - ' .	
											get_blog_details($row['blog_id'])->blogname . ' ('. $row['domain'] .')</option>';
									}
									else { // subdirectory mode
										echo '<option value="' . $row['blog_id'] . '"' . $selected . '>' . $row['blog_id'] . ' - ' .	
											get_blog_details($row['blog_id'])->blogname . ' ('. $row['domain'] . $row['path'] . ')</option>';
									}	
								}
							}			
						?>
						</select></p>
					</span>
										
					<h2 class="cloner-step">STEP 2: <span>Give the new site a Name</span></h2>
					<?php if($is_subdomain == true) { ?>
					<p><input id="new_site_name" name="new_site_name" type="text" value="<?php echo $_POST['new_site_name']; ?>"/>.<?php echo $current_site->domain; ?></p>
					<?php } else { ?>
					<p><?php echo $current_site->domain; ?>/<input id="new_site_name" name="new_site_name" type="text" value="<?php echo $_POST['new_site_name']; ?>" /></p>					
					<?php } ?>
					<h2 class="cloner-step">STEP 3: <span>Give the new site a Title</span></h2>
					<p><input id="new_site_title" name="new_site_title" value="<?php echo $_POST['new_site_title']; ?>" type="text" style="width: 300px;"/></p>
					
					<h2 class="cloner-step">STEP 4: <span>[OPTIONAL... but oh so awesome!]</span></h2>
					<div class="cloner-pro">
						<p class="no-border">Want to control more?<br /><b>Go pro.</b><span class="cloner-arrow"></span></p>
					</div>
					
					<h2 class="cloner-step">STEP 5:</h2>
						<input id="is_create" name="is_create" type="hidden" value="True"/>
						<input id="is_clone" name="is_clone" type="hidden" value="True"/>
						<p class="submit no-border">
							  <input name="Submit" value="<?php _e( 'Never Settle and Clone Away! &raquo;', 'ns_cloner' ) ?>" type="submit" /><br /><br />
							<i class="warning-txt"><span class="warning-txt-title">***WARNING:</span> We have made an incredibly complex process ridiculously easy with this powerful plugin. We have tested thoroughly and used this exact tool in our own live multisite environments. However, our comfort level should not dictate your precautions. If you're confident in your testing and the back-up scheme that you should have in place anyway, then by all means - start cloning like there's no tomorrow!</i>
						</p>
				</div>
				<!-- END Center Span Row -->
			</form>
			
			<!-- BEGIN Right Column -->			
				<div class="col-right">
					<h3>Pro Features</h3>
					<p>Want to control and automate a bunch more stuff like:</p>
					<ul>
					<li>Clone onto pre-existing sites</li>
					<li>Add users to the new site at clone-time</li>
					<li>Automate safe, global search and replace into clone</li>
					<li>Run safe, global search and replace on existing sites</li>
					<li>Log more detailed debug info</li>
					<li>Configure and save defaults for all settings</li>
					<li>and many more site cloning innovations!</li>
					</ul> 
					<p>???...then check out our Pro version [Coming soon!]</p>
					
					<p>In fact - help us finish it faster! Please donate (any amount is very appreciated), especially if you have found this useful and time=money saving!</p> 
					
					<p>Donate $10 or more and join our Early Adopters Club for privileged beta access and <b>LIFETIME FREE UPDATES</b> to our Pro version.</p>
					
					<p class="cloner-adopter">
						<img alt="Never Settle Cloner Early Adopter" src="<?php echo $this->adopter_img ?>" style="margin-right: 7px"/>
					</p>				
					
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="text-align: center;">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="53JXD4ENC8MM2">
						<input type="hidden" name="rm" value="2" >
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
					<br />
					<p><strong>Yes, we're serious!</strong> (The first 100 people to donate $10 or more will automatically become Never Settle Cloner Early Adopters).</p>
					
					<p>So, what are you waiting for?</p>				
				</div>
				<!-- END Right Column -->
			
			<?php
		echo '</div>';		
	}
	
	/**
	 * Execute actions
	 */
	function admin_init() {
		global $wpdb, $report, $count_tables_checked, $count_items_checked, $count_items_changed, $current_site, $is_subdomain;
		
		$page = isset( $_GET[ 'page' ] ) ? $_GET[ 'page' ] : '';
		if( 'ns-cloner' !== $page ) // stop function execution if not on plugin page
			return;

		if( ! current_user_can( 'manage_network_options' ) ) { // check user permissions
			wp_die( 'Stopped' );
			exit;
		}

		// Declare the locals that need to be available throughout the function:
		$target_id = '';
		$target_subd = '';
		$target_site = '';
		
		$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
		switch( $action ) {

			case 'process': // process action
			
				//  Start TIMER
				//  -----------
				$stimer = explode( ' ', microtime() );
				$stimer = $stimer[1] + $stimer[0];
				//  -----------
				
				// CREATE THE SITE
				if ($_POST['is_create']) {
						// Check for blank site name
						if ( $_POST['new_site_name'] !== '' && $_POST['new_site_title'] !== '')
						{
							// Create site
							$this->create_site($_POST['new_site_name'], $_POST['new_site_title']);
							// handle subdomain versus subdirectory modes
							if ($is_subdomain) {
								$this->status = $this->status . 'Created site <a href="http://' . $_POST['new_site_name'] . '.' . $current_site->domain . '" target="_blank"><b>http://'; 
								$this->status = $this->status . $_POST['new_site_name'] . '.' . $current_site->domain . '</b></a> with ID: <b>' . $this->target_id . '</b><br />';						
							}
							else {
								$this->status = $this->status . 'Created site <a href="http://' . $current_site->domain . '/' . $_POST['new_site_name'] . '" target="_blank"><b>http://'; 
								$this->status = $this->status . $current_site->domain . '/' . $_POST['new_site_name'] . '</b></a> with ID: <b>' . $this->target_id . '</b><br />';						
							}
						}
						else
						{
							// Clear the querystring and add the results
							wp_redirect( add_query_arg( 
								array('error' => 'true', 
									  'errormsg' => urlencode( __( 'You must specify a New Site Name and Title', 'ns_cloner' ) ), 
									  'updated' => false, 
									  'updatedmsg' => false), 
								wp_get_referer() ) ); 
							die;
						}
				}			
				
				// RUN THE CLONING
				if ($_POST['is_clone']) {
					$this->dlog( 'RUNNING cloning operation<br /><br />' );
					
					$source_id = $_POST['source_id'];
					// handle subdomain versus subdirectory modes
					if ($is_subdomain) {
						$source_subd = get_blog_details($source_id)->domain;
					}
					else {
						// don't want the trailing slash in path just in case there are replacements that don't have it
						$source_subd = get_blog_details($source_id)->domain . '/' . 
										str_replace('/', '', get_blog_details($source_id)->path);
					}
					$source_site = get_blog_details($source_id)->blogname;
					
					$target_id = $this->target_id;
					// handle subdomain versus subdirectory modes
					if ($is_subdomain) {
						$target_subd = $_POST['new_site_name'] . '.' . $current_site->domain;
					}
					else {
						$target_subd = $current_site->domain . '/' . $_POST['new_site_name'];
					}
					$target_site = $_POST['new_site_title'];
		
					if ( $source_id == '' || $source_subd == '' || $source_site == '' || $target_id == '' || $target_subd == '' || $target_site == '') {
						// Clear the querystring and add the results
						wp_redirect( add_query_arg( 
							array('error' => 'true', 
								  'errormsg' => urlencode( __( 'You must fill out all fields in Cloning section. Otherwise unsafe operation.', 'ns_cloner' ) ), 
								  'updated' => false, 
								  'updatedmsg' => false), 
							wp_get_referer() ) ); 
						die;
					}
					else
					{
						//configure all the properties
						$source_pre = $wpdb->base_prefix . $source_id . '_';	// the wp id of the source database
						$target_pre = $wpdb->base_prefix . $target_id . '_';	// the wp id of the target database
						
						$this->dlog ( 'Source Prefix: <b>' . $source_pre . '</b><br />' );
						$this->dlog ( 'Target Prefix: <b>' . $target_pre . '</b><br />' );
						
						//clone
						$this->run_clone($source_pre, $target_pre);
					}
				}				
				
				// RUN THE STANDARD REPLACEMENTS 
				$target_pre = $wpdb->base_prefix . $target_id . '_';	// the wp id of the target database
						
				//build replacement array
				//new-site-specific replacements
				$replace_array[$source_subd] = $target_subd;
				$replace_array[$source_site] = $target_site;
				$replace_array['blogs.dir/' . $source_id . '/'] = 'blogs.dir/' . $target_id . '/';
				$replace_array[str_replace(' ', '%20', $source_site)] = str_replace(' ', '%20', $target_site);
				//reset the option_name = wp_#_user_roles row in the wp_#_options table back to the id of the target site
				$replace_array[$wpdb->base_prefix . $source_id . '_user_roles'] = $wpdb->base_prefix . $target_id . '_user_roles';
				
				//replace
				$this->dlog ( 'running replace on Target table prefix: ' . $target_pre . '<br />' );
				foreach( $replace_array as $search_for => $replace_with) {
					$this->dlog ( 'Replace: <b>' . $search_for . '</b> >> With >> <b>' . $replace_with . '</b><br />' );
				}
				$this->run_replace($target_pre, $replace_array);
				
				// COPY ALL MEDIA FILES 
				$upload_dir = wp_upload_dir(); 
				$src_blogs_dir = str_replace('uploads', 'blogs.dir/' , $upload_dir['basedir']) . $source_id;
				$dst_blogs_dir = str_replace('uploads', 'blogs.dir/' , $upload_dir['basedir']) . $target_id;
				$num_files = recursive_file_copy($src_blogs_dir, $dst_blogs_dir, 0);
				$report .= ' and Copied: <b>' .$num_files . '</b> folders and files!<br />';
				
				// ---------------------------------------------------------------------------------------------------------------
				// Report

				//echo '<p style="margin:auto; text-align:center">';
				$this->dlog ( $report );

				//  End TIMER
				//  ---------
				$etimer = explode( ' ', microtime() );
				$etimer = $etimer[1] + $etimer[0];
				$this->log ( $target_subd . " cloned in " . ($etimer-$stimer) . " seconds."  );
				$this->dlog ( "Entire cloning process took: <strong>" . ($etimer-$stimer) . "</strong> seconds."  );
				//echo '</p>';
				//  ---------
				
				// Report on what was accomplished
				$this->status = $this->status . $report . "Entire cloning process took: <strong>" . number_format(($etimer-$stimer), 4) . "</strong> seconds... <br />";
				$this->status = $this->status . '<a href="' . $this->log_file_url . '" target="_blank">Historical Log</a> || ';
				$this->status = $this->status . '<a href="' . $this->detail_log_file_url . '" target="_blank">Detailed Log</a> ';
				
				// Clear the querystring and add the results
				wp_redirect( add_query_arg( 
					array('error' => false, 
						  'errormsg' => false, 
						  'updated' => 'true', 
						  'updatedmsg' => urlencode( __( $this->status, 'ns_cloner' ) )), 
					wp_get_referer() ) ); 
				die;
				
			default:
				break;
		}
	}


	/**
	 * Create site
	 */
	function create_site( $sitename, $sitetitle ) {
		global $wpdb, $current_site, $current_user;
		get_currentuserinfo();

		$blog_id = '';
		$user_id = '';
		$base = '/';

		$tmp_domain = strtolower( esc_html( $sitename ) );

		if( constant( 'VHOST' ) == 'yes' ) {
			$tmp_site_domain = $tmp_domain . '.' . $current_site->domain;
			$tmp_site_path = $base;
		} else {
			$tmp_site_domain = $current_site->domain;
			$tmp_site_path = $base . $tmp_domain . '/';
		}
		
		//------------------------------------------------------------------------------------
		// TO DO: 
		// - Feed these from fields on the admin page to allow other admins to be created
		// - Add ability to create new admin users at the same time
		//------------------------------------------------------------------------------------
		$create_user_name = '';
		$create_user_email = $current_user->user_email;
		$create_user_pass = '';
		$create_site_name = $sitename;
		$create_site_title = $sitetitle;
						
		$user = get_user_by_email( $create_user_email );

		if( ! empty( $user ) ) { // user exists
			$user_id = $user->ID;
		} else { // create user
			if( $create_user_pass == '' || $create_user_pass == strtolower( 'null' ) ) {
				$create_user_pass = wp_generate_password();
			}

			$user_id = wpmu_create_user( $create_user_name, $create_user_pass,  $create_user_email );
			if( false == $user_id ) {
				die( '<p>' . __( 'There was an error creating a user', 'ns_cloner' ) . '</p>' );
			} else {
				$this->log( "User: $create_user_name created with Password: $create_user_pass" );
			}
		}
		
		$site_id = get_id_from_blogname( $create_site_name );
		
		if( !empty( $site_id ) ) { // site exists
			// don't continue
			//die( '<p>' . __( 'That site already exists', 'ns_cloner' ) . '</p>' );
			// Clear the querystring and add the results
			wp_redirect( add_query_arg( 
				array('error' => 'true', 
					  'errormsg' => urlencode( __( 'That site already exists!', 'ns_cloner' ) ), 
					  'updated' => false, 
					  'updatedmsg' => false), 
				wp_get_referer() ) ); 
			die;
		}
		else
		{
			// create site and don't forget to make public:
			$meta['public'] = 1;
			$site_id = wpmu_create_blog( $tmp_site_domain, $tmp_site_path, $create_site_title, $user_id , $meta, $current_site->id );

			if( ! is_wp_error( $site_id ) ) {
				//send email
				//wpmu_welcome_notification( $site_id, $user_id, $create_user_pass, esc_html( $create_site_title ), '' );
				$this->log( 'Site: ' . $tmp_site_domain . $tmp_site_path . ' created!' );
				//assign target id for cloning and replacing
				$this->target_id = $site_id;
			} else {
				$this->log( 'Error creating site: ' . $tmp_site_domain . $tmp_site_path . ' - ' . $site_id->get_error_message() );
			}
		}	
	}
	
	/**
	 * Create / Add users
	 */
	function add_user($useremail, $username, $userpass = '', $userrole = 'administrator') {
	global $wpdb;
		$useremail = stripslashes($useremail);
		$username = stripslashes($username);
		$userpass = stripslashes($userpass);
		$is_new_user = 0;
	
		$user_id = '';
		$user = get_user_by_email( $useremail );

		if( ! empty( $user ) ) { // user exists
			$user_id = $user->ID;
		} else { // create user
			if( $userpass == '' || $userpass == strtolower( 'null' ) ) {
				$userpass = wp_generate_password();
			}

			$user_id = wpmu_create_user( $username, $userpass, $useremail );
			$is_new_user = 1;
		}
		
		if( false == $user_id ) {
			//die( '<p>' . __( 'There was an error creating a user', 'ns_cloner' ) . '</p> name: ' . $username . ' email: ' . $useremail . ' pass: ' . $userpass );
			$this->status = $this->status . '<font style="color:red;">' . "FAILED to create Username: <br>$username</b> with Email: <b>$useremail</b> - that username or email is probably already taken for a different user.</font><br />";
			$is_new_user = 0;
		} else {
			// add the user
			add_user_to_blog( $this->target_id, $user_id, $userrole );
			$this->status = $this->status . 'Added user: <b>' . $username . ' | ' . $useremail . '</b>';
			if ($is_new_user) {
				$this->status = $this->status . " created with Password: $userpass";
				$this->log('Added user: <b>' . $username . ' | ' . $useremail . " created with Password: $userpass");
			}
			$this->status = $this->status . '<br />';	
		}					
	}
	
	
	function run_clone($source_prefix, $target_prefix)
	{
		global $host, $db, $usr, $pwd, $report;
		
		//echo $host . ' ' . $usr . ' ' . $pwd . ' <br />';
		$cid = mysql_connect($host,$usr,$pwd); 
		
		// MUST ESCAPE '_' characters otherwise they will be interpreted as wildcard 
		// single chars in LIKE statement and can really hose up the database
		$SQL = 'SHOW TABLES LIKE \'' . str_replace('_','\_',$source_prefix) . '%\'';
		
		$tables_list = mysql_db_query($db, $SQL, $cid);
		$tables = mysql_fetch_array($tables_list);
		
		if ($_POST['is_debug']) { $this->dlog ( 'running sql - ' . $SQL . '<br />' ); }
			
		$num_tables = 0;
		
		if ($tables_list != false) {
			while ($tables = mysql_fetch_array($tables_list)) {
				$source_table = $tables[0];
				$target_table = str_replace($source_prefix, $target_prefix, $source_table);
				//if option is checked, ignore the existing posts tables 
				if (!$_POST['is_skip_posts']) {
					$num_tables++;
					//run cloning on current table to target table
					if ($source_table != $target_table) {
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />' );
						$this->dlog ( 'Cloning source table: <b>' . $source_table . '</b> (table #' . $num_tables . ') to Target table: <b>' . $target_table . '</b><br />' );	
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />' );
						$this->clone_table($source_table, $target_table);
					}
					else {
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
						$this->dlog ( 'Source table: <b>' . $source_table . '</b> (table #' . $num_tables . ') and Target table: <b>' . $target_table . ' are the same! SKIPPING!!!</b><br />');	
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
					}
				}
				elseif (strpos($tables[0],'post') === false) { 
					$num_tables++;
					if ($source_table != $target_table) {
						//run cloning on current table to target table
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
						$this->dlog ( 'Cloning source table: <b>' . $source_table . '</b> (table #' . $num_tables . ') to Target table: <b>' . $target_table . '</b><br />');	
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
						$this->clone_table($source_table, $target_table);
					}
					else {
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
						$this->dlog ( 'Source table: <b>' . $source_table . '</b> (table #' . $num_tables . ') and Target table: <b>' . $target_table . ' are the same! SKIPPING!!!</b><br />');	
						$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
					}
				}
				//if ($_POST['is_debug']) { echo '-----------------------------------------------------------------------------------------------------------<br /><br />'; }
			}
		}
		else {
			$this->dlog ( 'no data for sql - ' . $SQL );
		}
		
		if ($_POST['is_debug']) { $this->dlog ( '-----------------------------------------------------------------------------------------------------------<br /><br />'); }
		$report .= 'Cloned: <b>' .$num_tables . '</b> tables!';
		
		//mysql_close($cid); 
	}

	function backquote($a_name)
	{
		/*
			Add backqouotes to tables and db-names in
			SQL queries. Example from phpMyAdmin.
		*/
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
	} // function backquote($a_name, $do_it = TRUE)

	function sql_addslashes($a_string = '', $is_like = FALSE)
	{
		/*
			Better addslashes for SQL queries.
			Example from phpMyAdmin.
		*/
		if ($is_like) {
			$a_string = str_replace('\\', '\\\\\\\\', $a_string);
		} else {
			$a_string = str_replace('\\', '\\\\', $a_string);
		}
		$a_string = str_replace('\'', '\\\'', $a_string);

		return $a_string;
	} // function sql_addslashes($a_string = '', $is_like = FALSE)

	function clone_table($source_table, $target_table)
	{
		/*
			Reads the Database table in $source_table and executes SQL Statements for 
			cloning it to $target_table
		*/

		global $host, $db, $usr, $pwd;
		$sql_statements = '';
		$clone = mysql_connect($host,$usr,$pwd); 
		
		$query = "DROP TABLE IF EXISTS " . $this->backquote($target_table);
		if ($_POST['is_debug']) { $this->dlog ( $query . '<br /><br />'); }
		$result = mysql_db_query($db, $query, $clone);
		if ($result == FALSE) { $this->dlog ( '<b>ERROR</b> dropping table with sql - ' . $query . '<br /><b>SQL Error</b> - ' . mysql_error($clone) . '<br />'); }
		
		// Table structure - Get table structure
		$query = "SHOW CREATE TABLE " . $this->backquote($source_table);
		$result = mysql_db_query($db, $query, $clone);
		if ($result == FALSE) { $this->dlog ( '<b>ERROR</b> getting table structure with sql - ' . $query . '<br /><b>SQL Error</b> - ' . mysql_error($clone) . '<br />'); }
		else {
			if (mysql_num_rows($result) > 0) {
				$sql_create_arr = mysql_fetch_array($result);
				$sql_statements .= $sql_create_arr[1];
			}
			mysql_free_result($result);
		} // ($result == FALSE)
		
		// Create cloned table structure
		$query = str_replace($source_table, $target_table, $sql_statements);
		if ($_POST['is_debug']) { $this->dlog ( $query . '<br /><br />'); }
		$result = mysql_db_query($db, $query, $clone);
		if ($result == FALSE) { $this->dlog ( '<b>ERROR</b> creating table structure with sql - ' . $query . '<br /><b>SQL Error</b> - ' . mysql_error($clone) . '<br />'); }

		// Table data contents - Get table contents
		$query = "SELECT * FROM " . $this->backquote($source_table);
		$result = mysql_db_query($db, $query, $clone);
		if ($result == FALSE) { $this->dlog ( '<b>ERROR</b> getting table contents with sql - ' . $query . '<br /><b>SQL Error</b> - ' . mysql_error($clone) . '<br />'); }
		else {
			$fields_cnt = mysql_num_fields($result);
			$rows_cnt   = mysql_num_rows($result);
		} // if ($result == FALSE)

		// Checks whether the field is an integer or not
		for ($j = 0; $j < $fields_cnt; $j++) {
			$field_set[$j] = $this->backquote(mysql_field_name($result, $j));
			$type          = mysql_field_type($result, $j);
			// removed ||$type == 'timestamp' from this check because it's invalid - timestamp values need ' ' surrounding to insert successfully
			if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' ||
				$type == 'bigint') {
					$field_num[$j] = TRUE;
				} else {
					$field_num[$j] = FALSE;
				}
			} // end for		

			// Sets the scheme
			$entries = 'INSERT INTO ' . $this->backquote($target_table) . ' VALUES (';
			$search	= array("\x00", "\x0a", "\x0d", "\x1a"); 	//\x08\\x09, not required
			$replace	= array('\0', '\n', '\r', '\Z');
			$current_row	= 0;
			
			while ($row = mysql_fetch_row($result)) {
				$current_row++;
				// Tracks the _transient_feed_ and _transient_rss_ garbage for exclusion
				$is_trans = false;
				for ($j = 0; $j < $fields_cnt; $j++) {
					if (!isset($row[$j])) {
						$values[]     = 'NULL';
					} else if ($row[$j] == '0' || $row[$j] != '') {
						// a number
						if ($field_num[$j]) {
							$values[] = $row[$j];
						}
						else {
							// don't include _transient_feed_ bloat
							if (!$is_trans) {
								$values[] = "'" . str_replace($search, $replace, $this->sql_addslashes($row[$j])) . "'";
							}
							else {
								$values[]     = "''";
								$is_trans = false;
							}
							// set $is_trans for the next field based on the contents of the current field
							(strpos($row[$j],'_transient_feed_') === false && strpos($row[$j],'_transient_rss_') === false) ? $is_trans = false : $is_trans = true; 
								
						} //if ($field_num[$j])
					} else {
						$values[]     = "''";
					} // if (!isset($row[$j]))
				} // for ($j = 0; $j < $fields_cnt; $j++)
				
				// Execute current insert row statement						
				$query = $entries . implode(', ', $values) . ')';
				if ($_POST['is_debug']) { $this->dlog ( $query . '<br />'); }
				// Have to separate this into its own function otherwise it interfers with current mysql connection / results
				$this->insert_query($query);			
				
				unset($values);
			} // while ($row = mysql_fetch_row($result))
			mysql_free_result($result);

			//mysql_close($clone); 
		} //function clone_table($source_table, $target_table)
		
	function insert_query($query) {
		global $host, $db, $usr, $pwd;
		$insert = mysql_connect($host,$usr,$pwd); 
		$results = mysql_db_query($db, $query, $insert);
		if ($results == FALSE) { $this->dlog ( '<b>ERROR</b> inserting into table with sql - ' . $query . '<br /><b>SQL Error</b> - ' . mysql_error($clone) . '<br />'); }
	}

	function run_replace($target_prefix, $replace_array)
	{
		global $host, $db, $usr, $pwd, $report, $count_tables_checked, $count_items_checked, $count_items_changed;
		// Connect to DB
		// Set new_link param to true to avoid issues with WP
		$cid = mysql_connect($host,$usr,$pwd,true); 

		if (!$cid) { $this->dlog ("Connecting to DB Error: " . mysql_error() . "<br/>"); }

		// First, get a list of tables
		// MUST ESCAPE '_' characters otherwise they will be interpreted as wildcard 
		// single chars in LIKE statement and can really hose up the database
		$SQL = 'SHOW TABLES LIKE \'' . str_replace('_','\_',$target_prefix) . '%\'';

		$tables_list = mysql_db_query($db, $SQL, $cid);

		if (!$tables_list) {
		$this->dlog ("ERROR: " . mysql_error() . "<br/>$SQL<br/>"); } 

		// Loop through the tables

		while ($table_rows = mysql_fetch_array($tables_list)) {
			
			$table = $table_rows[0];
						
			$count_tables_checked++;
			$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
			$this->dlog ( 'Searching table: <b>' . $table . '</b><br />');  // we have tables!
			$this->dlog ( '-----------------------------------------------------------------------------------------------------------<br />');
			
			// ---------------------------------------------------------------------------------------------------------------
			
			$SQL = "DESCRIBE ".$table ;    // fetch the table description so we know what to do with it
			$fields_list = mysql_db_query($db, $SQL, $cid);
			
			// Make a simple array of field column names
			
			/*------------------------------------------------------------------------------------------------------------------
			*/
			$index_fields = "";  // reset fields for each table.
			$column_name = "";
			$table_index = "";
			$i = 0;
			
			while ($field_rows = mysql_fetch_array($fields_list)) {
				$column_name[$i++] = $field_rows['Field'];
				if ($field_rows['Key'] == 'PRI') $table_index[$i] = true ;
			}

			//    print_r ($column_name);
			//    print_r ($table_index);

			// now let's get the data and do search and replaces on it...
			
			$SQL = "SELECT * FROM ".$table;     // fetch the table contents
			$data = mysql_db_query($db, $SQL, $cid);
			
			if (!$data) {
			$this->dlog ("<br /><b>ERROR:</b> " . mysql_error() . "<br/>$SQL<br/>"); } 

			while ($row = mysql_fetch_array($data)) {

				// Initialize the UPDATE string we're going to build, and we don't do an update for each damn column...
				
				$need_to_update = false;
				$UPDATE_SQL = 'UPDATE '.$table. ' SET ';
				$WHERE_SQL = ' WHERE ';
				
				$j = 0;

				foreach ($column_name as $current_column) {
					
					// -- PROCESS THE SEARCH ARRAY --
					foreach( $replace_array as $search_for => $replace_with) {
						$j++;
						$count_items_checked++;

						//            echo "<br/>Current Column = $current_column";

						$data_to_fix = $row[$current_column];
						$edited_data = $data_to_fix;            // set the same now - if they're different later we know we need to update
						
						//            if ($current_column == $index_field) $index_value = $row[$current_column];    // if it's the index column, store it for use in the update
						
						$unserialized = unserialize($data_to_fix);  // unserialise - if false returned we don't try to process it as serialised
						
						if ($unserialized) {
							
							//                echo "<br/>unserialize OK - now searching and replacing the following array:<br/>";
							//                echo "<br/>$data_to_fix";
							//                
							//                print_r($unserialized);
							
							$this->recursive_array_replace($search_for, $replace_with, $unserialized);
							
							$edited_data = serialize($unserialized);
							
							//                echo "**Output of search and replace: <br/>";
							//                echo "$edited_data <br/>";
							//                print_r($unserialized);        
							//                echo "---------------------------------<br/>";
							
						}
						
						else {					
							if (is_string($data_to_fix)) $edited_data = str_replace($search_for,$replace_with,$data_to_fix) ;
						}
						
						if ($data_to_fix != $edited_data) {   // If they're not the same, we need to add them to the update string
							
							$count_items_changed++;
							
							if ($need_to_update != false) $UPDATE_SQL = $UPDATE_SQL.',';  // if this isn't our first time here, add a comma
							$UPDATE_SQL = $UPDATE_SQL.' '.$current_column.' = "'.mysql_real_escape_string($edited_data).'"' ;
							$need_to_update = true; // only set if we need to update - avoids wasted UPDATE statements
							
						}
						
						if ($table_index[$j]){
							$WHERE_SQL = $WHERE_SQL.$current_column.' = "'.$row[$current_column].'" AND ';
						}
						
					}
					//-- SEARCH ARRAY COMPLETE ----------------------------------------------------------------------
				}
				if ($need_to_update) {
					$count_updates_run;
					$WHERE_SQL = substr($WHERE_SQL,0,-4); // strip off the excess AND - the easiest way to code this without extra flags, etc.
					$UPDATE_SQL = $UPDATE_SQL.$WHERE_SQL;
					if ($_POST['is_debug']) { $this->dlog ( $UPDATE_SQL.'<br/><br/>'); }
					$result = mysql_db_query($db,$UPDATE_SQL,$cid);
					if (!$result) {
					$this->dlog (("<br /><b>ERROR: </b>" . mysql_error() . "<br/>$UPDATE_SQL<br/>")); } 
				}
			}
			/*---------------------------------------------------------------------------------------------------------*/
		}
		mysql_close($cid); 
	}

	function recursive_array_replace($find, $replace, &$data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				// check for an array for recursion
				if (is_array($value)) {
					$this->recursive_array_replace($find, $replace, $data[$key]);
				} else {
					// have to check if it's string to ensure no switching to string for booleans/numbers/nulls - don't need any nasty conversions
					if (is_string($value)) $data[$key] = str_replace($find, $replace, $value);
				}
			}
		} else {
			if (is_string($data)) $data = str_replace($find, $replace, $data);
		}
	} 
	
	/**
	 * Log
	 */
	function log( $message ) {
		error_log( date_i18n( 'Y-m-d H:i:s' ) . " - $message\n", 3, $this->log_file );
	}
	
	/**
	 * Detailed Log
	 */
	function dlog( $message ) {
		error_log( date_i18n( 'Y-m-d H:i:s' ) . " - $message\n", 3, $this->detail_log_file );
	}

}
	/**
	 * Copy files and directories recursively and return number of copies executed
	 */
	function recursive_file_copy($src, $dst, $num) {
		$num = $num + 1;
		if (is_dir($src)) {
			if (!file_exists($dst)) {
				mkdir($dst);
			}
			$files = scandir($src);
			foreach ($files as $file)
				if ($file != "." && $file != "..") $num = recursive_file_copy("$src/$file", "$dst/$file", $num); 
		}
		else if (file_exists($src)) copy($src, $dst);
		return $num;
	}


	/**
	 * Add admin external CSS sheet
	 */
	function add_ns_styles() {	
		// don't load the style on admin pages that aren't this plugin
		if (strpos($_SERVER['REQUEST_URI'], 'ns-cloner') === false) return;
		
		// change this path to load your own custom stylesheet
		$css_path = NS_CLONER_PLUGIN_URL . 'ns-cloner-style.css';
	 
		// registers your stylesheet
		wp_register_style( 'ns-Styles', $css_path );
	 
		// loads your stylesheet
		wp_enqueue_style( 'ns-Styles' );
	}

$ns_cloner_free = new ns_cloner_free();

