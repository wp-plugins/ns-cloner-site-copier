<div class="wrap ns-cloner-wrapper">
	
	<div class="ns-cloner-header">
		<a href="/wp-admin/network/admin.php?page=ns-cloner"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/ns-cloner-top-logo.png" alt="NS Cloner" /></a>
	</div>
	
	<form class="ns-cloner-form" action="?page=ns-cloner&action=process" method="post" enctype="multipart/form-data">
			
		<!-- report from last cloner operation -->
		<?php ns_cloner::render('report'); ?>
		
		<!-- premptive environment warnings -->
		<?php ns_cloner::render('preemptive-warnings'); ?>
		
		<!-- mode selector -->
		<div class="ns-cloner-section" id="ns-cloner-section-modes">
			<div class="ns-cloner-section-header">
				<h4><?php _e( 'Select Cloning Mode', 'ns-cloner' ); ?></h4>
				<span class="ns-cloner-collapse-all ns-cloner-grey-badge ns-cloner-pull-right">&#9650; <?php _e('Collapse All','ns-cloner'); ?></span>
				<span class="ns-cloner-expand-all ns-cloner-grey-badge ns-cloner-pull-right">&#9660; <?php _e('Expand All','ns-cloner'); ?></span>
			</div>
			<div class="ns-cloner-section-content">
				<select class="ns-cloner-select-mode" name="clone_mode">
					<?php foreach( $ns_cloner->clone_modes as $mode=>$details ): ?>
						<option value="<?php echo $mode; ?>" data-description="<?php echo apply_filters("ns_cloner_mode_description",$details["description"]); ?>" data-button-text="<?php echo apply_filters("ns_cloner_mode_button_text",$details["button_text"]); ?>">
							<?php echo $details["title"]; ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="ns-cloner-mode-description description"></p>
			</div>
		</div>
		
		<!-- sections -->
		<?php do_action('ns_cloner_render_ui'); ?>
		
		<!-- warning text -->
		<div class="ns-cloner-disclaimer">
			<strong><?php _e( 'WARNING:', 'ns-cloner' ); ?></strong>
			<?php _e( 'We have made an incredibly complex process ridiculously easy with this powerful plugin. We have tested thoroughly and used this exact tool in our own live multisite environments. However, our comfort level should not dictate your precautions. If you\'re confident in your testing and back-up scheme - which you should have in place anyway ;) - then by all means - start cloning like there\'s no tomorrow!', 'ns-cloner' ); ?>
		</div>
		
	</form>
	
	<!-- sidebar -->
	<div class="ns-cloner-sidebar">
		<?php ns_cloner::render('side-add-ons'); ?>
		<?php ns_cloner::render('side-shares'); ?>
	</div>
	
	<!-- clone button -->
	<div class="ns-cloner-button-wrapper">
		<input class="ns-cloner-button" type="submit" />
		<div class="ns-cloner-button-steps"></div>
	</div>		

</div>
	
	