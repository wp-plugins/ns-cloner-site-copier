<?php
// Reorder dependent WishlistMember tables
function ns_cloner_wlm_table_reorder( $tables, $db, $prefix ){
	$broadcast_tbl = $prefix.'wlm_emailbroadcast';
	$broadcast_tbl_index = array_search($broadcast_tbl,$tables);
	$queue_tbl = $prefix.'wlm_email_queue';
	$queue_tbl_index = array_search($queue_tbl,$tables);
	if( $broadcast_tbl_index && $queue_tbl_index ){
		$tables[ $broadcast_tbl_index ] = $queue_tbl;
		$tables[ $queue_tbl_index ] = $broadcast_tbl;
	}
	return $tables;
}
add_filter( 'ns_cloner_site_tables', 'ns_cloner_wlm_table_reorder', 11, 3);

