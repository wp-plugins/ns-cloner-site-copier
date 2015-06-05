<?php

class ns_cloner_section_select_source extends ns_cloner_section {
	
	public $modes_supported = array('core');
	public $id = 'select_source';
	public $ui_priority = 100;
	
	function render(){
		$this->open_section_box( $this->id, __("Select Source","ns-cloner") );
		?>
		<label class="ns-cloner-site-search-label">Search by url</label>
		<input type="text" class="ns-cloner-site-search" />
		<label class="ns-cloner-site-select-label">Or select</label>
		<select name="source_id" class="ns-cloner-site-select">
			<?php $sites = function_exists('wp_get_sites')? wp_get_sites(array('limit'=>9999)) : get_blog_list(0,'all'); ?>
			<?php foreach( $sites as $site ): ?>
			<option value="<?php echo $site['blog_id']; ?>">
				<?php $title = get_blog_details($site['blog_id'])->blogname; ?>
				<?php $url = is_subdomain_install()? "$site[domain]" : "$site[domain]$site[path]"; ?>
				<?php echo "$site[blog_id] - ".substr($title,0,30)." ($url)"; ?>
		  <?php endforeach; ?>
		</select>
		<p class="description ns-cloner-clear"><?php _e( 'Pick an existing source site to clone. If you haven\'t already, now is a great time to set up a "template" site exactly the way you want the new clone site to start out (theme, plugins, settings, etc.).','ns-cloner' ); ?></p>
		<?php
		$this->close_section_box();
	}
	
}
