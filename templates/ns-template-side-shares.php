<?php ns_sidebar::widget( 'rate', array('If the Cloner has saved you lots of time, tell everyone with a 5-star rating!','ns-cloner-site-copier') ); ?>
<?php ns_sidebar::widget( 'links', array('ns-cloner') ); ?>
<?php ns_sidebar::widget( 'subscribe' ); ?>
<?php ns_sidebar::widget( 'support', array('Have any issues with the cloner, or ideas on how to make it better? We\'d love to hear from you.') ); ?>
<?php ns_sidebar::widget( 'featured' ); ?>

<div class="ns-cloner-copy-logs">
	<div class="ns-cloner-copy-logs-content">
		<p><?php _e('If you\'re going to open a support request, could you please copy the log urls listed below and paste them at the bottom of your support request so we can give you better and faster help? Thank you!','ns-cloner'); ?></p>
		<p class="description"><?php _e('(Also, please send privately, not on a forum - all super-sensitive info such as passwords is hidden but some logged details like database prefixes, etc. are best kept private to be safe.)','ns-cloner'); ?></p>
		<textarea onclick="this.select();return false;"><?php echo join( "\n", ns_cloner::get_recent_logs() ); ?></textarea>
		<br/><br/>
		<a href="http://support.neversettle.it" class="button button-primary" target="_blank"><?php _e('Continue to Support','ns-cloner'); ?></a>
	</div>
</div>