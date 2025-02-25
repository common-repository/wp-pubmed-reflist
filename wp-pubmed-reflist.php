<?php
/*
Plugin Name: WP Pubmed Reflist 
Description: Shorttag Plugin to view an reference list from a pubmed query. use [pmid-refs key="key" limit=number]
where keys are associated with queries via a Settings page.
example:
[pmid-refs key="hu" limit=10]. 
Version: 1.41
Author: JimHu
Author URI: http://jimhu.org
License: GPL2
*/

if ( ! defined( 'WP_PUBMED_REFLIST_PLUGIN_DIR' ) )
	define( 'WP_PUBMED_REFLIST_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );

# include the code to make an admin page
include_once(WP_PUBMED_REFLIST_PLUGIN_DIR."/class.wpPubMedRefListSettings.php");

# include the class that processes queries into reference lists
include_once(WP_PUBMED_REFLIST_PLUGIN_DIR."/class.wpPubMedRefList.php");

# include the views class
include_once(WP_PUBMED_REFLIST_PLUGIN_DIR."/class.wpPubMedRefListViews.php");

# include the eFetch parser class
include_once(WP_PUBMED_REFLIST_PLUGIN_DIR."/class.PMIDeFetch.php");

/**
* Short tag function
* params key, limit 
*/

//add shortcodes
function wp_pubmed_reflist_shorttag($atts) {
	extract(shortcode_atts(array(
	'key'         => '',
	'limit'         => 20,
	'style'         => '',
	'wrap'         => 'ol',
	'linktext'	=> 'Search PubMed',
	'subset'	=> 1,
	'showlink' => ''
	), $atts));
	$p = new wpPubMedRefList;
	return $p->wp_pubmed_reflist($key, $limit, $style, $wrap, $linktext, $subset, $showlink);
}

add_shortcode( 'pmid-refs', 'wp_pubmed_reflist_shorttag');
