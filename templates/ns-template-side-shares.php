<div class="ns-cloner-side-widget">
	<div class="ns-cloner-side-widget-content ns-cloner-text-center">
		<p><?php _e( 'If the Cloner has saved you lots of time, tell everyone with a 5-star rating!', 'ns-cloner'); ?></p>
		<p><a href="http://wordpress.org/support/view/plugin-reviews/ns-cloner-site-copier?rate=5#postform" target="_blank" class="ns-cloner-blue-badge">Rate it 5 Stars</a></p>
	</div>
</div>
<div class="ns-cloner-side-widget ns-cloner-side-widget-no-border">
	<div class="ns-cloner-side-widget-content">
		<a href="http://neversettle.it/home/?utm_campaign=cloner+ns+home&utm_source=ns+cloner&utm_medium=plugin&utm_content=social+button+to+ns" target="_blank"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/ns-visit.png" alt="Visit NS" /></a>
		<a href="http://facebook.com/neversettle.it" target="_blank"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/ns-like.png" alt="Like NS" /></a>
		<a href="https://twitter.com/neversettleit" target="_blank"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/ns-follow.png" alt="Follow NS" /></a>
	</div>
</div>
<div class="ns-cloner-side-widget">
	<div class="ns-cloner-side-widget-content ns-cloner-text-center">
		<p><?php _e( 'Stay up to date with updates, new plugins and all the good stuff!', 'ns-cloner' ); ?></p>
		<!-- Begin MailChimp Signup Form -->
		<link href="//cdn-images.mailchimp.com/embedcode/classic-081711.css" rel="stylesheet" type="text/css"></style>
		<div id="mc_embed_signup">
		<form action="//NeverSettle.us8.list-manage.com/subscribe/post?u=a979a91d50433ca0485c903ee&amp;id=15ee335def" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
		<div class="mc-field-group">
		<label for="mce-EMAIL">Email Address </label>
		<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL" placeholder="Email Address">
		</div>
		<div id="mce-responses" class="clear">
		<div class="response" id="mce-error-response" style="display:none"></div>
		<div class="response" id="mce-success-response" style="display:none"></div>
		</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
		   <div style="position: absolute; left: -5000px;"><input type="text" name="b_a979a91d50433ca0485c903ee_15ee335def" tabindex="-1" value=""></div>
		   <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
		</form>
		</div>
		<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script>
		<script type='text/javascript'>
		(function($) {
		window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[7]='MMERGE7';ftypes[7]='text';fnames[10]='MMERGE10';ftypes[10]='number';fnames[11]='MMERGE11';ftypes[11]='number';fnames[12]='MMERGE12';ftypes[12]='text';fnames[13]='MMERGE13';ftypes[13]='text';fnames[14]='MMERGE14';ftypes[14]='text';fnames[15]='MMERGE15';ftypes[15]='text';fnames[16]='MMERGE16';ftypes[16]='text';fnames[17]='MMERGE17';ftypes[17]='text';fnames[18]='MMERGE18';ftypes[18]='text';
		}(jQuery));
		var $mcj = jQuery.noConflict(true);
		</script>
		<!--End mc_embed_signup-->
	</div>
</div>
<div class="ns-cloner-side-widget">
	<div class="ns-cloner-side-widget-content ns-cloner-text-center">
		<p><?php _e( 'Have any issues with the cloner, or ideas on how to make it better? We\'d love to hear from you.', 'ns-cloner' ); ?></a></p>
		<p><a href="http://support.neversettle.it" class="ns-cloner-copy-logs-trigger ns-cloner-blue-badge" target="_blank"><?php _e( 'Support & Feature Requests', 'ns-cloner' ); ?></a></p>
		<div class="ns-cloner-copy-logs">
			<div class="ns-cloner-copy-logs-content">
				<p><?php _e('If you\'re going to open a support request, could you please copy the log urls listed below and paste them at the bottom of your support request so we can give you better and faster help? Thank you!','ns-cloner'); ?></p>
				<textarea><?php echo join( "\n", ns_cloner::get_recent_logs() ); ?></textarea>
				<br/><br/>
				<a href="http://support.neversettle.it" class="button button-primary" target="_blank"><?php _e('Continue to Support','ns-cloner'); ?></a>
			</div>
		</div>
	</div>
</div>
<div class="ns-cloner-side-widget" id="ns-cloner-other-plugin">
	<a href="http://neversettle.it/automate-wordpress-seo-custom-fields" target="_blank">
		<img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>/images/ns-seo-automation.png" class="full-image" />
	</a>
</div>
