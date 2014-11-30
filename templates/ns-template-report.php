<?php
$last_clone_report = get_site_transient('ns_cloner_report_'.get_current_user_id());
if( $last_clone_report != false ){
	?>
	<div class="ns-cloner-report">
		<div class="ns-cloner-report-content">
			<h5><?php echo $last_clone_report['_message']; ?></h5>
			<?php if( isset($last_clone_report['_warning']) ): ?>
				<span class="ns-cloner-error-message"><?php echo $last_clone_report['_warning']; ?></span>
			<?php endif; ?>
			<?php foreach( $last_clone_report as $label => $value ): ?>
				<?php
				// skip special/hidden messages
				if( in_array( $label, array('_message','_warning') ) ) continue;
				// format links - for logs just display the last 
				if( preg_match('/^http/',$value) ){
					$value = "<a href='$value' target='_blank'>".str_replace(NS_CLONER_V3_PLUGIN_URL,'',$value)."</a>";
				}
				?>
				<div class="ns-cloner-report-item">
					<div class="ns-cloner-report-item-label"><?php echo $label; ?>:</div>
					<div class="ns-cloner-report-item-value"><?php echo $value; ?></div>
				</div>
			<?php endforeach; ?>
			<br/><br/>
			<input type="button" class="button button-primary ns-cloner-close-report" value="<?php _e("OK, Close Report",'ns-cloner'); ?>" />
		</div>
	</div>
	<?php
	delete_site_transient('ns_cloner_report_'.get_current_user_id());
}
?>