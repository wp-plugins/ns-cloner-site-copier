<?php

class ns_cloner_section_create_target extends ns_cloner_section {
	
	public $modes_supported = array('core','ajax_validate');
	public $id = 'create_target';
	public $ui_priority = 200;
	
	function render(){
		$this->open_section_box( $this->id, __("Create New Site","ns-cloner"), false, __("Create Site","ns-cloner") );
		?>
		<label for="target_title"><?php _e( "Give the Target site a Title", "ns-cloner" ); ?></label>
		<input type="text" name="target_title" placeholder="New Site H1"/>
		<label for="target_name"><?php _e( "Give the Target site a URL (or \"Name\" in WP terminology)", "ns-cloner" ); ?></label>
		<?php if( is_subdomain_install() ): ?>
			<input type="text" name="target_name" />.<?php echo preg_replace( '|^www\.|', '', get_current_site()->domain ); ?>
		<?php else: ?>
			<?php echo get_current_site()->domain . get_current_site()->path; ?><input type="text" name="target_name" />
		<?php endif; ?>	
		<?php
		$this->close_section_box();
	}
	
	function validate($errors){
		$user = apply_filters( 'ns_wp_create_site_admin', wp_get_current_user() );
		$site_meta = apply_filters( 'ns_wp_create_site_meta', array("public"=>1) );
		// use wp's built in wpmu_validate_blog_signup validation for all new site vars
		// also, use a test on  a known valid name/title to filter out any validation errors added by other plugins via the wpmu_validate_blog_signup filter
		$baseline_validation = wpmu_validate_blog_signup( 'nsclonervalidationtest', 'NS Cloner Validation Test' );
		$current_site_validation = wpmu_validate_blog_signup( $this->cloner->request["target_name"], $this->cloner->request["target_title"], $user );
		$site_errors = array_diff( $current_site_validation['errors']->get_error_messages(), $baseline_validation['errors']->get_error_messages() );
		foreach( $site_errors as $error ){
			// if the error is only because there are dashes in the site name, ignore the error since that's fine/allowable
			if( $error=='Only lowercase letters (a-z) and numbers are allowed.' && preg_match('/^[a-z0-9-]+$/',$this->cloner->request["target_name"]) ) continue;
			// otherwise add the error to the list so it can get sent back
			$errors[] = array('message'=>$error,'section'=>$this->id);
		}
		return $errors;
	}
	
}