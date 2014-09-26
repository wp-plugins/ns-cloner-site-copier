<?php

require_once (dirname(__FILE__).'/ns-log-utils.php');

/**
 * Organize a sequence of search/replace values (adding new corrective search/replace pairs) to avoid compounding replacement issues
 * @param array - **by reference** - $search strings of search text to sort/process
 * @param array - **by reference** - $replace strings of replacement text to sort/process (keeping same order as $search of course so no mix ups)
 * @param array - **by reference** - $regex_search strings of regular expressions (this func may add to the array but won't manipulate anything already in it)
 * @param array - **by reference** - $regex_replace strings of replacement text for $regex_search (also may be added to but will not be otherwise modified)
 * @return void
 */
function ns_set_search_replace_sequence( &$search, &$replace, &$regex_search, &$regex_replace, $logfile=false ){
	$fix_insertion_index = 1;
	// Sort string replacements by order longest to shortest to prevent a situation like 
	// Source Site w/ url="neversettle.it",uploaddir="/neversettle.it/wp-content/uploads" and Target Site w/url="blog.neversettle.it",uploaddir="/neversettle.it/wp-content/uploads/sites/2"
	// resulting in target uploaddir being "/blog.neversettle.it/wp-content/uploads" when url replacement is applied before uploaddir replacement
	$search_replace = array_unique( array_combine( $search, $replace ) );
	uksort( $search_replace, create_function('$a,$b','return strlen($b)-strlen($a);') );
	$search = $new_search = array_keys( $search_replace );
	$replace = $new_replace = array_values( $search_replace );
	// If any search terms are found in replace terms which have already been inserted (ie came earlier in the find/replace sequence), remove this search term plus
	// its accompanying replacement from the string-based search/replace and a correction to change that replacement back again so replacements won't be compounded.
	// This prevents a situation like Source Site w/ title="Brown",url="brown.com" and Target Site w/ title="Brown Subsite",url="subsite.brown.com"
	// resulting in target urls like "subsite.Brown Subsite.com" when title replacement is applied after url replacement.
	$regex_search = $regex_replace = $repeated_exact_conflicts = array();
	foreach( $search as $index => $search_text ){
		// figure out what the desired replace text is from other array in case we need it
		$replace_text = $replace[$index];
		// get replacements earlier in array (which could've already been inserted into text so we need to watch out for them)
		$past_replacements = array_slice( $replace, 0, $index );
		// identify any of those replacements which the search text and save as array of conflicts
		$conflicting_replacements = array_filter( $past_replacements, create_function('$past_replace_text','return stripos($past_replace_text,"'.$search_text.'")!==false;') );
		if( !empty($conflicting_replacements) ){
			ns_log_write( "Conflicting replacement found: search text '$search_text' appears in one or more previous replacement(s): '".join("','",$conflicting_replacements)."'", $logfile );
			foreach( $conflicting_replacements as $conflicting_replacement ){
				// if it's an exact match, assume it's supposed to happen and skip fixing it - not 100% sure what the right logic is here, but seems more likely to be right this way
				if( $conflicting_replacement == $search_text ){
					ns_log_write( "Conflicting exact match replacement found: '$search_text'. Skipping since this is most likely supposed to happen", $logfile );
					continue;
				}
				// insert into the search/replace arrays right after the current item which will produce the bad replacement
				array_splice( $new_search, $fix_insertion_index, 0, str_ireplace($search_text,$replace_text,$conflicting_replacement) );
				array_splice( $new_replace, $fix_insertion_index, 0, $conflicting_replacement );
				$fix_insertion_index++;
			}
		}
		$fix_insertion_index++;
	}
	$search = $new_search;
	$replace = $new_replace;
}

/**
 * Recursively search and replace
 * @param mixed $data - **by reference** - string or array which should have find/replace applied
 * @param array $search array with text values to find
 * @param array $replace array with text values to replace $search values with
 * @param array $regex_search array with regular expressions to look for
 * @param array $regex_replace array with text value to replace $regex_search values with
 * @return int number of replacements made
 */
function ns_recursive_search_replace( &$data, $search, $replace, $regex_search=array(), $regex_replace=array(), $case_sensitive=false ){
	$is_serialized = is_serialized($data);
	$string_replacements_made = $regex_replacements_made = 0;
	// unserialize if need be
	if( $is_serialized ){
		$data = unserialize($data);
	}
	// run through replacements for strings, arrays - other types are unsupported to vaoid
	if( is_array($data) ){
		foreach ($data as $key => $value) {
			ns_recursive_search_replace( $data[$key], $search, $replace, $regex_search, $regex_replace, $case_sensitive );
		}
	}
	elseif( is_string($data) ){
		// simple string replacment - most of the time this is all that is needed
		$replace_func = $case_sensitive? 'str_replace' : 'str_ireplace';
		$data = $replace_func( $search, $replace, $data, $string_replacements_made );
		// advanced regex replacement - this will be skipped most of the time
		if( !empty($regex_search) && !empty($regex_replace) ){
			$data = preg_replace(
				$regex_search,
				$regex_replace,
				$data,
				-1, // no limit to replacements
				$regex_replacements_made
			);
		}
	}
	// reserialize if need be
	if( $is_serialized ){
		$data = serialize($data);
	}
	// return count of replacements made
	return $string_replacements_made + $regex_replacements_made;
}


/**
 * Register a notice to be shown on the cloner page the next time it is opened 
 * Could be this page, or a subsequent one depending on whether this is called before/after admin_notices
 * Could be extended later to be dismissable
 * @param string $message pre-localized / printf-ed string to show
 * @param string $type css class applied - options are "error"(red), "updated"(green), and "update-nag"(yellow)
 * @param string $page which page (dictated by the GET page param), if only one, should this shown on
 * @param boolean $network whether to show on network or single-site admin page
 */
function ns_add_admin_notice( $message, $type="error", $page="all", $network=false ){
	$transient_name = $network? "ns_network_admin_notices" : "ns_admin_notices";
	$saved_notices = get_site_transient( $transient_name );
	$notices = is_array($saved_notices)? $saved_notices : array();
	$notices[] = array(
		"message" => $message,
		"type" => $type,
		"page" => $page,
	);
	set_site_transient( $transient_name, $notices, 5*MINUTE_IN_SECONDS );
}

/**
 * Show all queued admin notices for the current admin page
 * @return void
 */
function ns_show_admin_notices(){
	$transient_name = is_network_admin()? "ns_network_admin_notices" : "ns_admin_notices";
	$notices = get_site_transient( $transient_name );
	if( is_array($notices) ){
		foreach( $notices as $index=>$notice ){
			if( $notice["page"]=="all" || $notice["page"]==$_GET['page'] ){
				echo "<div class='$notice[type]'><p>$notice[message]</p></div>\n";
				unset($notices[$index]);	
			}
		}
		set_site_transient( $transient_name, $notices, 5*MINUTE_IN_SECONDS );
	}
}
add_action( "admin_notices", "ns_show_admin_notices" );
add_action( "network_admin_notices", "ns_show_admin_notices" );

/**
 * Detect if a certain admin page is being displayed
 * @param string $slug menu slug of page to check for
 * @return boolean whether or not we're on the specified page
 */
function ns_is_admin_page( $slug ){
	return is_admin() && isset($_GET["page"]) && $_GET["page"] == $slug;
}

/**
 * Detect if a subpage of a certain admin page is being displayed
 * @param string $slug menu slug of parent page to check for
 * @return boolean whether or not we're on a subpage of the specified page
 */
function ns_is_admin_subpage( $slug ){
	global $submenu;
	if( isset( $submenu[$slug] ) && isset( $_GET['page'] ) ){
		$submenu_items = $submenu[$slug];
		foreach( $submenu_items as $item ){
			// if there exists under the specified parent page a sub with the same slug as the current $_GET page var, this func is true
			if( is_admin() && $item[2] == $_GET['page']){
				return true;
			}
		}
	}
	return false;
}



?>