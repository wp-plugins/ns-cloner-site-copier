<?php

class ns_cloner_section_copy_users_cta extends ns_cloner_section {
	
	public $modes_supported = array('core');
	public $id = 'copy_users_cta';
	public $ui_priority = 400;
	
	function render(){
		$this->open_section_box( $this->id, __('Copy Users','ns-cloner') );
		?>
		<p class="description"><?php _e( 'The NS Cloner can do a LOT more! Checkout the Add-ons area or <a href="http://neversettle.it/ns-cloner-bundles-features" target="_blank">Compare all available Bundles and features!</a>', 'ns-cloner' ); ?></p>
		<?php
		$this->close_section_box();
	}
	
}
