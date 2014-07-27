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
		// use wp's built in wpmu_validate_blog_signup validation for all new site vars
		$user = apply_filters( 'ns_wp_create_site_admin', wp_get_current_user() );
		$site_meta = apply_filters( 'ns_wp_create_site_meta', array("public"=>1) );
		$wp_validation = wpmu_validate_blog_signup( $this->cloner->request["target_name"], $this->cloner->request["target_title"], $user );
		$site_errors = $wp_validation['errors']->get_error_messages();
		foreach( $site_errors as $error ){
			$errors[] = array('message'=>$error,'section'=>$this->id);
		}
		return $errors;
	}
	
}
