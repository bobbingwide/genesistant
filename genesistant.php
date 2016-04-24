<?php // (C) Copyright Bobbing Wide 2015, 2016
/*
Plugin Name: genesistant
Plugin URI: http://www.oik-plugins.com/oik-plugins/genesistant
Description: Genesis theme framework assistant 
Version: 0.0.1
Author: bobbingwide
Author URI: http://www.oik-plugins.com/author/bobbingwide
Text Domain: genesistant
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2015-2016 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

genesistant_loaded();

/**
 * Function to invoke when genesistant is loaded
 *
 * Whether or not we start genesistant processing depends on the 
 * the constant GENESISTANT
 * 
 * Constant | Value | Processing
 * -------- | ----- | --------------
 * not defined | n/a | Trace all genesis hooks
 * defined  | true  | Trace all genesis hooks
 * defined  | false | Don't trace genesis hooks
 *
 */
function genesistant_loaded() {
	add_action( "oik_fields_loaded", "genesistant_oik_fields_loaded" );
	if ( !defined( "GENESISTANT" ) || GENESISTANT !== false ) {
  	add_action( "all", "genesistant_all", 10, 2 );
	}
}	

/**
 * Trace all genesis hooks
 * 
 * So we can attempt to see what hook causes Genesis to do something.
 * Use View source and look for all the genesis hook names inside HTML comments
 * 
 * Notes:
 * - it's not safe to produce HTML comments before the doctype tag has been created
 * - we're really only interested in hooks prefixed 'genesis_'
 * - but there are some others that are of great interest too
 *
 * @param string $tag the action hook or filter
 * @param mixed $args parameters? 
 */
function genesistant_all( $tag, $args2=null ) {
	static $ok_to_e_c = false;
	if ( $ok_to_e_c ) {
		if ( 0 === strpos( $tag, "genesis_" ) ) {
			$hooked = genesistant_get_hooks( $tag );
			genesistant_safe_e_c( $tag, $hooked );
		} elseif ( 0 === strpos( $tag, "the_excerpt" ) ) {
			$hooked = genesistant_get_hooks( $tag );
			genesistant_safe_e_c( $tag, $hooked );
		} elseif ( 0 === strpos( $tag, "the_content" ) ) {
			$hooked = genesistant_get_hooks( $tag );
			genesistant_safe_e_c( $tag, $hooked );
		} elseif ( 0 === strpos( $tag, "the_permalink" ) ) {
			$hooked = genesistant_get_hooks( $tag );
			genesistant_safe_e_c( $tag, $hooked );
		}
	} else {
		if ( "genesis_doctype" === $tag ) {
			$ok_to_e_c = true;
		}
	}
}

/**
 * Only echo comments when safe
 *
 * If we're processing a filter it's not safe to echo anything
 * so we defer the output until it's an action
 * There could be quite a lot of deferred output.
 * 
 * @param string $tag the hook name
 * @param string $hooked information about attached hooks 
 */
function genesistant_safe_e_c( $tag, $hooked ) {
	static $deferred = null;
	$hook_type = genesistant_trace_get_hook_type( $tag );
	if ( $hook_type === "action" ) {
		if ( $deferred ) {
			genesistant_e_c( "deferred $deferred" );
			$deferred = null;
		}
		genesistant_e_c( "$hook_type $tag $hooked" );
	
	} else {
		$deferred .= "\n";
		$deferred .= "$hook_type $tag $hooked";
	}
}
	
/** 
 * Return the hook type
 * 
 * This relies on the $wp_actions array being incremented right at the start of do_action() and do_action_ref_array()
 * BUT not to be called for apply_filters() and  apply_filters_ref_array()
 *
 * @param string $hook
 * @return string "action" or "filter" 
 */ 
function genesistant_trace_get_hook_type( $hook ) {
	global $wp_actions;
	if ( isset( $wp_actions[ $hook ] ) ){
		$type = "action";
	} else {
		$type = "filter";
	}
	return( $type );
}

/**
 * Return the current filter summary
 * 
 * Even if current_filter exists the global $wp_current_filter may not be set
 * 
 * @return string current filter array imploded with commas
 */
function genesistant_current_filter() {
  global $wp_current_filter;
  if ( is_array( $wp_current_filter ) ) { 
	  $filters = implode( ",",  $wp_current_filter );
	} else {
	  $filters = null;
	}		
  return( $filters );  
}

/**
 * Return the attached hooks
 *
 * Note: It's safe to use foreach over $wp_filter[ $tag ]
 * since this routine's invoked for the 'all' hook
 * not the hook in question.
 * But I've copied the code for bw_trace_get_attached_hooks() anyway
 * since it's more 'complete' 
 *
 * See {@link http://php.net/manual/en/control-structures.foreach.php}
 *
 * @param string $tag the action hook or filter
 * @return string the attached hook information
 *
 */
function genesistant_get_hooks( $tag ) {
	global $wp_filter; 
  if ( isset( $wp_filter[ $tag ] ) ) {
		$current_hooks = $wp_filter[ $tag ];
		//bw_trace2( $current_hooks, "current hooks for $tag", false, BW_TRACE_VERBOSE );
		$hooks = null;
		$hooks = genesistant_current_filter();
		$hooks .= "\n";
		foreach ( $current_hooks as $priority => $functions ) {
			$hooks .= "\n: $priority  ";
			foreach ( $functions as $index => $args ) {
				$hooks .= " ";
				if ( is_object( $args['function' ] ) ) {
					$object_name = get_class( $args['function'] );
					$hooks .= $object_name; 

				} elseif ( is_array( $args['function'] ) ) {
					//bw_trace2( $args, "args" );
					if ( is_object( $args['function'][0] ) ) { 
						$object_name = get_class( $args['function'][0] );
					}	else {
						$object_name = $args['function'][0];
					}
					$hooks .= $object_name . '::' . $args['function'][1];
				} else {
					$hooks .= $args['function'];
				}
				$hooks .= ";" . $args['accepted_args'];
			}
		}
		
	} else {
		$hooks = null;
	}
	return( $hooks ); 
}

/**
 * Echo a comment
 *
 * @param string $string the text to echo inside the comment
 */
if ( !function_exists( "genesistant_e_c" ) ) { 
function genesistant_e_c( $string ) {
	echo "<!--\n";
	echo $string;
	echo "-->";
}
}


/**
 * Callback for virtual field "no_title"
 *
 * Unfortunately this function gets called too late
 * The shortcode is not expanded until the title has been displayed
 *
 */
function genesistant_no_title() {
	remove_action( 'genesis_entry_header', 'genesis_do_post_title' );	
	//gob();
}

/**
 * Implement "oik_fields_loaded" for genesistant
 */
function genesistant_oik_fields_loaded() {
	$the_title_args = array( "#callback" => "genesistant_no_title"
                         , "#parms" => "_oik_sc_code" 
                         , "#plugin" => "genesistant"
                         , "#file" => "includes/no-title.php"
                         , "#form" => false
                         , "#hint" => "virtual field"
                         ); 
  bw_register_field( "no_title", "virtual", "Turns title display off", $the_title_args );
}
