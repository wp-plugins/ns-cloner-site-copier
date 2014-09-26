<div class="wrap ns-cloner-wrapper">
	
	<div class="ns-cloner-header">
		<a href="/wp-admin/network/admin.php?page=ns-cloner"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/ns-cloner-top-logo.png" alt="NS Cloner" /></a>
	</div>
	
	<h2 class="ns-cloner-addons-title">
		<?php _e( 'NS Cloner Add-On Manager', 'ns-cloner' ); ?>
		<span class="ns-cloner-addons-display-toggle">
			<span class="ns-cloner-addons-display-grid active"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/icon-grid.png" alt="Show as Grid"/></span>
			<span class="ns-cloner-addons-display-list"><img src="<?php echo NS_CLONER_V3_PLUGIN_URL; ?>images/icon-list.png" alt="Show as List"/></span>
		</span>
	</h2>
	
	<ul class="ns-cloner-addons grid">
	<?php $addons = fetch_feed( NS_CLONER_V3_ADDON_FEED ); ?>
		<?php if( !is_wp_error($addons) ): ?>
		<?php foreach($addons->get_items() as $item): ?>
		<li>
			<?php
			$addon_thumbnail_el = $item->get_item_tags('http://wordpress.org/plugins/ns-cloner-site-copier/','thumbnail');
			$addon_php_classname_el = $item->get_item_tags('http://wordpress.org/plugins/ns-cloner-site-copier/','class_name');
			$classes_to_check = array_map( 'trim', explode( ',', $addon_php_classname_el[0]['data'] ) );
			$classes_to_check_installed = array_filter( $classes_to_check, create_function('$class','return class_exists($class);') );
			$is_installed = sizeof($classes_to_check)>0 && sizeof($classes_to_check) == sizeof($classes_to_check_installed);
			$is_new = !is_null( $item->get_item_tags('http://wordpress.org/plugins/ns-cloner-site-copier/','is_new') );
			?>
			<a href="<?php echo $item->get_link(); ?>" target="_blank">
				<img class="ns-cloner-addon-thumb" src="<?php echo $addon_thumbnail_el[0]['data']; ?>" />
			</a>
			<div class="ns-cloner-addon-content">
				<h3>
					<a href="<?php echo $item->get_link(); ?>" target="_blank"><?php echo $item->get_title(); ?></a>
					<?php if($is_new): ?><span class="ns-cloner-new-addon"></span><?php endif; ?>
				</h3>
				<?php echo wpautop( $item->get_description(true) ); ?>
				<div class="ns-cloner-addon-cta-buttons">
					<a href="<?php echo $item->get_link(); ?>" class="ns-cloner-grey-badge"  target="_blank"><?php _e( 'Learn More', 'ns-cloner' ); ?></a>
					<?php if( $is_installed ): ?>
						<span class="ns-cloner-green-badge"><?php _e( 'Installed', 'ns-cloner' ); ?></span>
					<?php else: ?>
						<a href="<?php echo $item->get_link(); ?>" class="ns-cloner-blue-badge" target="_blank"><?php _e( 'Buy Now', 'ns-cloner' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</li>
		<?php endforeach; ?>
		<?php else: ?>
			<li>Unable to fetch addon list.</li>
		<?php endif; ?>
	</ul>
	
	
</div>
