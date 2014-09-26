<div class="ns-cloner-side-widget" id="ns-cloner-addons">
	<h5><?php _e( 'Add-Ons', 'ns-cloner' ); ?></h5>
	<ul>
		<?php $addons = fetch_feed( NS_CLONER_V3_ADDON_FEED ); ?>
		<?php if(! is_wp_error($addons) ): ?>
		<?php foreach($addons->get_items() as $item): ?>
		<li>
			<?php
			$addon_php_classname_el = $item->get_item_tags('http://wordpress.org/plugins/ns-cloner-site-copier/','class_name');
			$classes_to_check = array_map( 'trim', explode( ',', $addon_php_classname_el[0]['data'] ) );
			$classes_to_check_installed = array_filter( $classes_to_check, create_function('$class','return class_exists($class);') );
			$is_installed = sizeof($classes_to_check)>0 && sizeof($classes_to_check) == sizeof($classes_to_check_installed);
			$is_new = !is_null( $item->get_item_tags('http://wordpress.org/plugins/ns-cloner-site-copier/','is_new') );
			?>
			<a href="<?php echo $item->get_link(); ?>" target="_blank" class="<?php if($is_new) echo 'ns-cloner-new-addon'; ?>"><?php echo $item->get_title(); ?></a>
			<?php if( $is_installed ): ?>
				<span class="ns-cloner-green-badge ns-cloner-pull-right ns-cloner-small"><?php _e( 'Installed', 'ns-cloner' ); ?></span>
			<?php else: ?>
				<a href="<?php echo $item->get_link(); ?>" class="ns-cloner-blue-badge ns-cloner-pull-right ns-cloner-small" target="_blank"><?php _e( 'Buy / Info', 'ns-cloner' ); ?></a>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
		<?php else: ?>
			<li>Unable to fetch addon list.</li>
		<?php endif; ?>
	</ul>
</div>