<?php

/**
 * This is the base class for all NS Cloner Addon Plugins which will be implemented as individual plugins.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if(  !class_exists('ns_cloner_addon') ):	
class ns_cloner_addon {

	// Class vars
	var $cloner = null;
	var $title = '';
	var $plugin_path = '';
	var $plugin_url = '';
	var $ns_plugin_text_domain = '';
	
	function __construct(){
		// this action means cloner will have loaded so safe to call init to boot up addon
		add_action( 'ns_cloner_construct', array($this,'init'), 10, 1 );
		add_action( 'ns_cloner_admin_init', array($this,'admin_init') );
	}
	
	function init($cloner){
		$this->cloner = $cloner;
		$this->cloner->register_addon( $this );
	}
	
	function admin_init(){}
	

}
endif;
