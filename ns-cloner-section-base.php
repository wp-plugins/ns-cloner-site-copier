<?php

/*
 * This is the base class for all NS Cloner section which are sub-divisions of individual addons/plugins 
 * Sections are self-contained functionality + UI that alter or augment the cloning pipeline  
 * Child classes should provide render() and validate() functions (those will automatically be called at proper point)
 */ 

class ns_cloner_section {

	protected $cloner;          // instance of ns_cloner class passed by addon and used for logging, referencing current values, etc
	public $id;                 // string slug to use as id for the html section and any other time section needs to be referenced
	public $is_active; 			// bool of whether this section's hooks should be applied for the current operation, set by checkbox in UI
	public $modes_supported;	// array of strings with each mode supported 
	public $ui_priority;		// int order to be displayed relative to other sections (lower=sooner)
	
	function __construct($cloner) {
		$this->cloner = $cloner;
		add_action( 'ns_cloner_render_ui', array($this,'render'), $this->ui_priority );
		// if this section is supported for current clone mode, set active and run init (where all process modification, etc will take place) and
		// setup validation so that child section classes can just put stuff in their validate function and have it work automatically
		if( in_array( $this->cloner->current_clone_mode, $this->modes_supported ) ){
			// make sure addons haven't been disabled
			if( isset($this->cloner->request['disable_addons']) && $this->cloner->request['disable_addons']==true ) return;
			// trigger setup
			$this->is_active = true;
			add_action( 'ns_cloner_before_everything', array($this,'init') );
			add_filter( 'ns_cloner_valid_errors', array($this,'validate') );
		}
	}
	
	function init(){
	}
	
	function render() {
		// overide in each local instance
		// should include/echo html output
	}
	
	function open_section_box( $id, $title, $help_text='', $step_tagline_for_clone_button='' ){
		?>
		<section class="ns-cloner-section" id="ns-cloner-section-<?php echo $id; ?>" data-modes="<?php echo join(' ',$this->modes_supported); ?>" data-button-step="<?php echo $step_tagline_for_clone_button; ?>">
			<div class="ns-cloner-section-header">
				<h4><?php echo $title; ?></h4>
				<span class="ns-cloner-section-help"><?php echo $help_text; ?></span>
				<span class="ns-cloner-section-collapse"></span>
				<?php /* TODO = add toggle ability */ ?>
			</div>
			<div class="ns-cloner-section-content">
		<?php
		do_action( "ns_cloner_open_section_box_{$this->id}" );
	}
	
	function close_section_box(){
		do_action( "ns_cloner_close_section_box_{$this->id}" );
		?>
			</div><!-- /.ns-cloner-section-content -->
		</section><!-- /.ns-cloner-section -->
		<?php
	} 

	function validate($errors){
		// override in each local instance
		// should add any error messages to the $errors array and return it
		return $errors;
	}
	
}

?>