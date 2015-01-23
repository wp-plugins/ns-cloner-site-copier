<?php

// User Access Manager compatibility patch
// uses the same column name ("object_id") as a WP table so format type must be explictly set to avoid incorrect casting 
function ns_cloner_uam_format_fix( $format, $source_table, $target_table ){
	if( preg_match('/uam_accessgroup_to_object$/',$source_table) ){
		$format = array('%s','%s','%d');
	}
	return $format;
}
add_filter( 'ns_cloner_insert_format', 'ns_cloner_uam_format_fix', 10, 3 );

// CSS JS Toolbox compatibility patch
function ns_cloner_cjtoolbox_post_insert_fix( $values, $source_table, $target_table ){
	global $wpdb;
	if( preg_match('/cjtoolbox_templates$/',$source_table) && is_null($values['guid']) ){
		$wpdb->query( $wpdb->prepare( 'UPDATE '.esc_sql($target_table).' SET guid = NULL WHERE id=%d', $values['id'] ) );
	}
}
add_action( 'ns_cloner_after_insert', 'ns_cloner_cjtoolbox_post_insert_fix', 10, 3 );
