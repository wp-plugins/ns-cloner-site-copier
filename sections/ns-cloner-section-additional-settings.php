<?php

class ns_cloner_section_additional_settings extends ns_cloner_section {
	
	public $modes_supported = array('core');
	public $id = 'additional_settings';
	public $ui_priority = 800;
	
	function render(){
		$this->open_section_box( $this->id, __("Additional Settings","ns-cloner") );
		?>
		<label>
			<input type="checkbox" name="debug" /> <?php _e('Capture in-depth debugging info in logs (basic information always logged)','ns-cloner'); ?>
		</label>
		<?php
		$this->close_section_box();
	}
	
}
