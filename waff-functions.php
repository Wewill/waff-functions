<?php
/*
Plugin Name: WAFF Functions
Plugin URI: http://www.wilhemarnoldy.fr
Description: Bunch of functions from Template 1 to WAFF two theme & template : pre_get_posts, shortcodes, types, views, ordering ... 
Author: Wilhem Arnoldy
Author URI: http://www.wilhemarnoldy.fr
Version: 2.5
*/

//namespace WaffTwo\Functions;

if( ! defined( 'ABSPATH') ) {
    exit;
}

// Load text domain
// -------------------
define('WAFF_DIR', plugin_dir_path( __FILE__ ) );
define('WAFF_PO_PLUGINPATH', '/' . dirname(plugin_basename( __FILE__ )));
define('WAFF_PO_TEXTDOMAIN', 'waff');

add_action('plugins_loaded', 'waff_load_textdomain');
function waff_load_textdomain() {
	load_plugin_textdomain( WAFF_PO_TEXTDOMAIN, false, WAFF_PO_PLUGINPATH.'/languages/' );
}

/* LOAD PARENT ADMIN SCRIPTS
================================================== */
function waff_admin_scripts($hook) {
	// $screen       = get_current_screen();
	// $screen_id    = $screen ? $screen->id : '';
    wp_enqueue_script( 'custom-admin', plugins_url('/js/custom-admin.js',__FILE__),'','',true);
}
add_action( 'admin_enqueue_scripts', 'waff_admin_scripts', 30);


// Theme custom chimpy form
if ( defined('WAFF_NEWSLETTER_USE_CHIMPY') && true === WAFF_NEWSLETTER_USE_CHIMPY ) 
	include( 'chimpy/custom-form-chimpy.php' );

// ADD PARTNERS COLORIZE fcts
if ( defined('WAFF_HAS_PARTNERS_COLORIZED_IMAGES') && true === WAFF_HAS_PARTNERS_COLORIZED_IMAGES ) 
	include( 'colorizing/partners-functions.php' );

// ADD SECTION COLORIZE fcts
if ( defined('WAFF_HAS_SECTIONS_COLORIZED_IMAGES') && true === WAFF_HAS_SECTIONS_COLORIZED_IMAGES )
	include( 'colorizing/sections-functions.php' );



/**
 * General functions 
 * =================================================================
 * =================================================================
 * =================================================================
 * =================================================================
 */


/**
 * Add author dropdown selector for medias page
 */
function media_add_author_dropdown()
{
    $scr = get_current_screen();
    if ( $scr->base !== 'upload' ) return;

    $author   = filter_input(INPUT_GET, 'author', FILTER_SANITIZE_STRING );
    $selected = (int)$author > 0 ? (int)$author : '-1';
    $args = array(
        'show_option_none'   => __('All authors', 'waff'),
        'name'               => 'author',
        'selected'           => $selected
    );
    wp_dropdown_users( $args );
}
add_action('restrict_manage_posts', 'media_add_author_dropdown');

/**
 * Add author filter to query 
 * > NOT NEEDED since ?
 */
function author_filter($query) {
    if ( is_admin() && $query->is_main_query() ) {
        if (isset($_GET['author']) && $_GET['author'] == -1) {
            $query->set('author', '');
        }
    }
}
//add_action('pre_get_posts','author_filter');

/**
 * Convert post_tag ( Tags ) tax from flat to hierarchical
 */ 
function custom_hierarchical_tags_register() {

	// Maintain the built-in rewrite functionality of WordPress tags
	global $wp_rewrite;
  
	$rewrite =  array(
	  'hierarchical'              => false, // Maintains tag permalink structure
	  'slug'                      => get_option('tag_base') ? get_option('tag_base') : 'tag',
	  'with_front'                => ! get_option('tag_base') || $wp_rewrite->using_index_permalinks(),
	  'ep_mask'                   => EP_TAGS,
	);
  
	// Redefine tag labels (or leave them the same)
  
	$labels = array(
	  'name'                       => __( 'Tags' ),
	  'singular_name'              => __( 'Tag' ),
	  'menu_name'                  => __( 'Tags' ),
	  'all_items'                  => __( 'All Tags'),
	  'parent_item'                => __( 'Parent Tag'),
	  'parent_item_colon'          => __( 'Parent Tag:'),
	  'new_item_name'              => __( 'New Tag Name'),
	  'add_new_item'               => __( 'Add New Tag'),
	  'edit_item'                  => __( 'Edit Tag'),
	  'update_item'                => __( 'Update Tag'),
	  'view_item'                  => __( 'View Tag'),
	  'separate_items_with_commas' => __( 'Separate tags with commas'),
	  'add_or_remove_items'        => __( 'Add or remove tags'),
	  'choose_from_most_used'      => __( 'Choose from the most used'),
	  'popular_items'              => __( 'Popular Tags'),
	  'search_items'               => __( 'Search Tags'),
	  'not_found'                  => __( 'Not Found'),
	);
  
	// Override structure of built-in WordPress tags
  
	register_taxonomy( 'post_tag', 'post', array(
	  'hierarchical'              => true, // Was false, now set to true
	  'query_var'                 => 'tag',
	  //'labels'                    => get_taxonomy_labels('post_tag'),
	  'labels'                    => $labels,
	  'rewrite'                   => $rewrite,
	  'public'                    => true,
	  'show_ui'                   => true,
	  'show_admin_column'         => true,
	  '_builtin'                  => true,
	  'show_in_rest'              => true,
	  'rest_base'                 => 'tags',
	) );
  
}
add_action('init', 'custom_hierarchical_tags_register');

/**
 * Filters 
 * =================================================================
 * =================================================================
 * =================================================================
 * =================================================================
 */

/**
 * pre_get_posts : Modifiy tax numbers of posts + Order taxs by title ASC
 * > Maybe add a !is_admin() ? 
 */
function change_tax_num_of_posts( $wp_query ) {
    if( $wp_query->is_tax() && $wp_query->is_main_query()) {
        $wp_query->set('posts_per_page', 12);
        $wp_query->set( 'order', 'ASC' );
        $wp_query->set( 'orderby', 'title' );
    }
}
add_action('pre_get_posts', 'change_tax_num_of_posts' );

/**
 * WAFFTWO
 * pre_get_posts: Section query > taxonomy-section.php
 * LIMIT TO approved and programmed 
 * ORDER BY
 * > Not working 
 * TODO : https://wordpress.stackexchange.com/questions/385235/how-to-orderby-multiple-meta-fields-if-some-fields-are-empty
*/
add_filter( 'pre_get_posts', 'taxonomy_section_sort_order' );
function taxonomy_section_sort_order( $query ) {
    if ( is_admin() || !$query->is_main_query() )
		return;

	if ( $query->is_tax('section') && $query->is_main_query() ) {
		// Args
		$meta_query =   array(
			'relation' => 'AND',
			'status' => array(
					'key' => '_status',
					'value' => ['approved','programmed'],
					'compare' => 'IN',
			),
			array ( 
				'relation' => 'OR',
				'empty_order_title' => array(
					'key' => 'wpcf-f-order-title',
					'compare' => 'NOT EXISTS'
				),
				'order_title' => array(
					'key' => 'wpcf-f-order-title',
					'compare' => 'EXISTS'
				),
			),
			array(
				'relation' => 'OR',
				'empty_french_title' => array(
					'key' => 'wpcf-f-french-operating-title',
					'compare' => 'NOT EXISTS'
				),
				'french_title' => array(
					'key' => 'wpcf-f-french-operating-title',
					'compare' => 'EXISTS'
				),
			),
		);

		// Set meta queries 
   		$query->set('meta_query', $meta_query);
		$query->set('posts_per_page', 100);

		// Multiple order
		$query->set('orderby', array('order_title' => 'ASC', 'french_title' => 'ASC', 'title' => 'ASC'));

		return $query;
	}

	return $query;
}

/**
 * Types and views shortcodes 
 * =================================================================
 * =================================================================
 * =================================================================
 * =================================================================
 */

/**
 * Adds a trim_shortcode
 */
//if (function_exists('wpv_do_shortcode')) {
	function trim_shortcode($atts, $content = '') {
		$content = wpv_do_shortcode($content);
		$length = (int)$atts['length'];
		if (strlen($content) > $length) {
			$content = substr($content, 0, $length) . '&hellip;';
		}
		return $content;
	}
	add_shortcode('trim', 'trim_shortcode');
//}

/**
 * Adds a check if a child post exists shortcode
 */
if (function_exists('types_child_posts')) {
	function child_posts_exist_func( $atts ){
		$child_posts = types_child_posts('child-post-type-slug');
		if ($child_posts) {
			return 1;
		} else {
			return 0;
		}
	}
	add_shortcode( 'has-child-posts', 'child_posts_exist_func' );
}

/**
 * Adds a get current date shortcode
 */
function get_date_ts_func( $atts ){
	$today = getdate();
	return $today[0];
}
add_shortcode( 'get_date_ts', 'get_date_ts_func' );

/**
 * Adds a shortcode to count indexes
 */
function views_index() {
    global $WP_Views;
    static $i = 0;
    $i ++;
    return $i;
}
add_shortcode('wpv-post-index', 'views_index');

/**
 * Adds a shortcode to count post found by a view
 */
//https://toolset.com/forums/topic/get-no-of-results-returned-by-view-in-template-after-all-filters-have-applied/
function views_post_count( $atts ) {
	$atts = shortcode_atts( [
	//   'wpvprchildof' => 0
	], $atts );
	// $args = array( 'wpvprchildof'=> $atts['wpvprchildof'] );
	$filtered_posts = get_view_query_results();
	return count($filtered_posts);	
}
add_shortcode('wpv-post-count', 'views_post_count');

/**
 * Adds a begin shortcodes to extract from a html span span span the first match 
 * > Used in views 
 */
//if (function_exists('wpv_do_shortcode')) {
	function begin_shortcode($atts, $content = '') {
		$content = wpv_do_shortcode($content);
		preg_match_all('/<span\s*class="[^>]+">(.*?)<\/span>/', $content, $match);
		return $match[0][0];
	}
	add_shortcode('begin', 'begin_shortcode'); // #43 Ancien shortcode 
//}

function func_get_begin_shortcode($atts, $content = '') {
	$atts  = shortcode_atts( array(
        'output' => 'raw',
	), $atts );
	$p_start_and_stop_time = get_post_meta( get_the_id(), 'wpcf-p-start-and-stop-time', false );
	switch ($atts['output']) {
	case 'html':
		return ($p_start_and_stop_time[0]['begin'] != '')?'<span class="begin">' . esc_html($p_start_and_stop_time[0]['begin']) . '</span>':'';
	break;
	case 'sep':
		return ($p_start_and_stop_time[0]['begin'] != '')?'<span class="begin">' . esc_html($p_start_and_stop_time[0]['begin']) . '</span> › ':'';
	break;
	default:
		return esc_html($p_start_and_stop_time[0]['begin']);
	break;
	}
}
add_shortcode('get_begin', 'func_get_begin_shortcode'); // #43 Nouveau shortcode 

function func_get_end_shortcode($atts, $content = '') {
	$atts  = shortcode_atts( array(
        'output' => 'raw',
	), $atts );
	$p_start_and_stop_time = get_post_meta( get_the_id(), 'wpcf-p-start-and-stop-time', false );
	switch ($atts['output']) {
	case 'html':
		return ($p_start_and_stop_time[0]['end'] != '')?'<span class="end muted">' . esc_html($p_start_and_stop_time[0]['end']) . '</span>':'';
	break;
	case 'sep':
		return ($p_start_and_stop_time[0]['end'] != '')?' › <span class="end muted">' . esc_html($p_start_and_stop_time[0]['end']) . '</span>':'';
	break;
	default:
		return esc_html($p_start_and_stop_time[0]['end']);
	break;
	}
}
add_shortcode('get_end', 'func_get_end_shortcode'); // #43 Nouveau shortcode 
//}

/**
 * Adds a get a parent term shortcodes
 * > Used in views 
 */
function parentterm_shortcode($atts, $content = '') {
	$atts  = shortcode_atts( array(
        'term' => '',
        'termtaxonomy' => 'room',
        'format' => 'name',
	), $atts );

	// error_log(print_r($atts['term'], true));
	
	if ( $atts['term'] == '' ) {
		// error_log("##NO TERMS");
		$post_id = get_queried_object_id();
		// error_log($post_id);
		$terms = get_the_terms( $post_id, $atts['termtaxonomy'] );
		// error_log(print_r($terms, true));
		if ( empty($terms) ) return '';
		$atts['term'] = $terms[0]->slug; 
		// error_log(print_r($atts['term'], true));
	}

   	$tax = get_term_by('slug', $atts['term'], $atts['termtaxonomy']);
	// error_log(print_r($tax, true));
	// No parent returns ''
   	if ( $tax->parent == 0 ) return '';
  	$parent_tax = get_term_by('term_id', $tax->parent, $atts['termtaxonomy']);

	$output = '';
	switch ($atts['format']) {
	case 'name':
		$output = $parent_tax->name; 
	break;
	case 'url':
		$output = get_term_link($parent_tax->term_id, $atts['termtaxonomy']);
	break;
	case 'slug':
		$output = $parent_tax->slug; 
	break;
	default:
		$output = $parent_tax->name; 
	break;
	}
	
	return $output;
}
add_shortcode('parent_term', 'parentterm_shortcode');

/**
 * Adds a shortcodes to check if a contact ID has contents to show 
 * > Used in views 
 */
function has_contact_content_func($atts, $content = ''){
	$atts  = shortcode_atts( array(
        'ids' => '',
	), $atts );

	$contact_ids = explode(',', $atts['ids']); // print_r($contact_ids);

	foreach($contact_ids as $contact_id) {
		$contact_content = get_post($contact_id);
		$id = $contact_content->ID;
		$content = $contact_content->post_content; // print_r($content);
		$title = $contact_content->post_title;
		$picture = get_post_meta( $id, 'wpcf-c-picture', true);
		$biofilmography_french = get_post_meta( $id, 'wpcf-c-biofilmography-french', true); // print_r($biofilmography_french);
		// $biofilmography_english = get_post_meta( $id, 'wpcf-c-biofilmography-english', true);

		if ( !empty($contact_content->post_content) )
			return 1;
		
		if ( !empty($biofilmography_french) )
			return 1;
	}
}
add_shortcode( 'has_contact_content', 'has_contact_content_func' );

/**
 * Add SC to wp-types ref SC 
 * > Already added in general preferences  
 */
function prefix_add_my_shortcodes($shortcodes) {
    $shortcodes[] = 'has_contact_content';
    return $shortcodes;
}
add_filter('wpv_custom_inner_shortcodes', 'prefix_add_my_shortcodes');

/**
 * Add a shortcode to get rooms list 
 * > Used in views 
 */
function get_rooms_func($atts, $content = ''){
	$atts  = shortcode_atts( array(
        'ids' => '',
    ), $atts );

	$taxonomy_name = 'room';
	$room_id = intval($atts['ids']);

	$args=array(
    	'taxonomy' 	=> $taxonomy_name,
		'orderby' => 'name',
        'order' => 'ASC',
        'parent'	=> 0,
    	'hide_empty' => false,
    );

	$terms = get_terms($args);

	if  ($terms) {
	$html= '<ul class="room-list">';
	  foreach ($terms  as $term ) {
	  	$hide_rooms = get_term_meta($term->term_id, 'wpcf-r-hide-in-website', true);
	  	if ( $term->parent == 0 ) {

  			if ( $hide_rooms != 1 )
				$terms_children = get_term_children( $term->term_id, $taxonomy_name );
				if ( count($terms_children) != 0 ) {
					$html .= '<li class="room-item parent" data-slug="'.$term->slug.'">' . $term->name . '</li>';
				} else { 
					$html .= '<li class="room-item parent child" data-slug="'.$term->slug.'"><a href="' . esc_url( get_term_link( $term ) ) . '" alt="' . esc_attr( sprintf( __( 'View all post filed under %s', 'waff' ), $term->name ) ) . '">' . $term->name . '</a><div class="details-by-room" data-slug="'.$term->slug.'" data-color="'.get_term_meta($term->term_id, 'wpcf-r-color', true).'"></div></li>';
				}
			  
				foreach ($terms_children  as $term_children ) {
					$t = get_term_by( 'id', $term_children, $taxonomy_name );
					$hide_room = get_term_meta($t->term_id, 'wpcf-r-hide-in-website', true);
					if ( $hide_room != 1 )
					$html .= '<li class="room-item child" data-slug="'.$t->slug.'"><a href="' . esc_url( get_term_link( $t ) ) . '" alt="' . esc_attr( sprintf( __( 'View all post filed under %s', 'waff' ), $t->name ) ) . '">' . $t->name . '</a><div class="details-by-room" data-slug="'.$t->slug.'" data-color="'.get_term_meta($t->term_id, 'wpcf-r-color', true).'"></div></li>';
				}
	  	}
	  }
	  $html .= '</ul>';
	}

	if ( !empty($html) ) {
		return $html;
	}
}
add_shortcode( 'get_rooms', 'get_rooms_func' );

/**
 * Add a shortcode to get the day number by the real day 
 * > Used in views 
 */
function func_get_day_number($atts, $content = ''){
	global $current_edition;
  	// Extract atts
	  extract( shortcode_atts( array(
        'date' => null,
    ), $atts ));

	setlocale(LC_TIME, 'fr_FR.UTF8');
	$today = getdate();
	$edition_start_date_meta = get_term_meta($current_edition->term_id, 'wpcf-e-start-date', True);
	$edition_end_date_meta = get_term_meta($current_edition->term_id, 'wpcf-e-end-date', True);
	$edition_start_date = date('d', $edition_start_date_meta);//Y-m-d
	$edition_end_date = date('d', $edition_end_date_meta);
	$edition_current_date = date('d', $atts['date']);

	$count = 0;
	for ($day = $edition_start_date; $day <= $edition_end_date; $day++) { //$day <= $edition_end_date+1
		$count++;
		if ( $day == $edition_current_date )
			$theday = $count;
	}
	return (int)$theday;
}
add_shortcode( 'get_day_number', 'func_get_day_number' );

/**
 * Loop into a view to build calendar projection
 * > Used in views 
 */
function get_projection_days_func($atts, $content = ''){
	global $current_edition;

	/* Modele v1 week=1 & week=2 <td class="day" data-date="1573776000"><p class="impact-text-number">15</p>[get_rooms]<div class="details"></div></td>*/
	/* Modele v2 week=0
	    <!-- Day 1  -->
		<div class="mx-1 ml-0 p-3 flex-fill day active" data-bs-toggle="collapse" href="#day_{ID}" role="button" aria-expanded="false" aria-controls="day_{ID}">
			<div class="d-flex justify-content-center">
				<div class="d-flex flex-column">
					<span class="subline">Lundi</span><span class="display-2 light d-block mt-1">15</span>
				</div>
			</div>
			<p class="text-center mb-4"><i class="icon icon-down"></i></p>
			<ul class="projections-list list-unstyled">

			</ul>					
		</div>
	*/
	
  	// Extract atts
    extract( shortcode_atts( array(
		'week' => 0,
		'show_rooms' => 'false',
	), $atts ));

	$show_rooms = ($atts['show_rooms'] == 'true')?true:false;

	setlocale(LC_TIME, 'fr_FR.UTF8');
	$today = getdate();
	$edition_start_date_meta = get_term_meta($current_edition->term_id, 'wpcf-e-start-date', True);
	$edition_end_date_meta = get_term_meta($current_edition->term_id, 'wpcf-e-end-date', True);
	$edition_start_date = date('d', $edition_start_date_meta);//Y-m-d
	$edition_end_date = date('d', $edition_end_date_meta);
	//$edition_start_date_ts = $edition_start_date->getTimestamp();
	//$edition_end_date_ts = $edition_end_date->getTimestamp();
	
	$count = 1;
	$html_week1 = array();
	$html_week2 = array();
	$html_weekAll = array();
//	for ($day = $edition_start_date; $day <= $edition_end_date; $day++) { //$day <= $edition_end_date+1 // FIFAM
	for ($day = $edition_start_date_meta; $day <= $edition_end_date_meta; $day+=(60*60*24)) { //$day <= $edition_end_date+1 // Issue from DINARD, count must be from timestamp and not day
			// $day = timestamp 
		if ($count <= 5) {
			$html_week1[] = '<td class="day" data-date="'.(($edition_start_date_meta-82800) + (60 * 60 * (24 * $count-1))).'"><p class="impact-text-number">'.date('j', $day).'</p>'.do_shortcode('[get_rooms]').'<div class="details"></div></td>';
		} else {
			if ( $day <= $edition_end_date )
				$html_week2[] = '<td class="day" data-date="'.(($edition_start_date_meta-82800) + (60 * 60 * (24 * $count-1))).'"><p class="impact-text-number">'.date('j', $day).'</p>'.do_shortcode('[get_rooms]').'<div class="details"></div></td>';
			else 
				$html_week2[] = '<td class="day unactive hidden-xs" data-date="'.(($edition_start_date_meta-82800) + (60 * 60 * (24 * $count-1))).'"><p class="impact-text-number">'.date('j', $day).'</p>'.do_shortcode('[get_rooms]').'<div class="details"></div></td>';
		}

		$html_weekAll[] = sprintf('
			<!-- Day %s  -->
			<div class="card rounded-0 mx-1 ml-0 p-3 %s flex-fill day --actives --unactive --has-projections %s" data-date="%s">
				<div class="card-body p-0 m-0 d-flex flex-column %s">
					<div class="part_one">
						<div class="d-flex justify-content-center">
							<div class="d-flex flex-column">
								<span class="subline">%s</span><span class="display-2 light d-block mt-1">%s</span>
							</div>
						</div>
						<p class="text-center mb-4 d-none d-sm-block"><i class="icon icon-down"></i></p>
					</div>
					<div class="part_two">
						<ul class="projections-list list-unstyled details"></ul><!-- Rempli en JS via "Fiche film" -->
						%s
					</div>
					<div class="part_three">
						%s
					</div>
				</div>
			</div>',
			$count,
			(( $show_rooms == true )?'mb-3':''),
			(($day >= $edition_end_date_meta)?'unactive d-none d-xl-block':''),
			(($edition_start_date_meta-82800) + (60 * 60 * (24 * $count-1))),
			(( $show_rooms == true )?'justify-content-start':'justify-content-between'),
			date_i18n('l', (($edition_start_date_meta-82800) + (60 * 60 * (24 * $count-1)))),
			date('j', $day),
			(( $show_rooms == true )?do_shortcode('[get_rooms]'):''),
			(( $show_rooms == false )?'<span class="badge rounded-pill bg-action-1 color-white subline"><i class="icon-down-right"></i> le même jour</span>':''),
			//$count
		);

		//print_r('<br>'.$day .' / '. $count);
		$count++;
	}
	
	
	// print_r('<br>'.$edition_start_date);
	// print_r('<br>'.$edition_end_date);
	// print_r('<br>'.$edition_start_date_meta);
	// print_r('<br>'.$edition_end_date_meta);
	// print_r($html_week1);
	// print_r($html_week2);
	// print_r($html_weekAll);
	
	
	if ( $week == 1 )
		return implode("\r\n", $html_week1);
	else if ( $week == 2 )
		return implode("\r\n", $html_week2);
	else if ( $week == 0 )
		return implode("\r\n", $html_weekAll);
	else 
		return implode("\r\n", $html_weekAll);
}
add_shortcode( 'get_projection_days', 'get_projection_days_func' );


/**
 * Dynamically populate select field from Types via shortcode and $_GET
 * > Used in types 
 */
//
function populate_exfunc_populate_expresspress($atts, $content = ''){
  	// Extract atts
	  extract( shortcode_atts( array(
    ), $atts ));
	// GET VAR
	$express   = filter_input(INPUT_GET, 'express', FILTER_SANITIZE_STRING );
    $output = (int)$express == 1 ? '1' : '0';
	return $output;
}
add_shortcode( 'populate_express', 'func_populate_express' );





// // exclude_posts_with_meta_from_orderby: Exlcude meta post from orderby / evite d'afficher les films non selectionnes
// function exclude_posts_with_meta_from_orderby( $query ) {
//     if( is_admin() || !$query->is_orderby() )
//         return;

// //    $query->set('meta_query', array(
// //        'relation' => 'OR',
// //	        array(
// //	            'key'   => '_status',
// //	            'value' => 'approved'
// //	        ),
// //	        //some posts don't have a sponsor_post_type meta field set, so check for those too
// //	        array(
// //	        	'key' => '_status',
// //	        	'compare' => 'NOT EXISTS'
// //	        )
// //    	)
// //    );
// /*
// 	$args = array(
// 		'post_type' => 'film',
// 		//get all posts
// 		'posts_per_page' => -1,
// 		//return an array of post IDs
// 		'fields' => 'ids',
// 		//now check for posts that have a _status that is not 'approved'
// 		'meta_query' => array(
// 			'relation' => 'OR',
// 				array(
// 					'key'   => '_status',
// 					'value' => 'approved',
// 				'compare' => '!='
// 				),
// 			//some posts don't have a sponsor_post_type meta field set, so check for those too
// 			array(
// 				'key' => '_status',
// 				'compare' => 'NOT EXISTS'
// 			)
// 		)
// 	);
//       $excluded_ids = get_posts($args);
// */

// 	$args = array(
// 		'post_type' => 'film',
// 		//get all posts
// 		'posts_per_page' => -1,
// 		//return an array of post IDs
// 		'fields' => 'ids',
// 		//now check for posts that have a _status that is  'approved'
// 		'meta_query' => array(
// 			array(
// 				'key'   => '_status',
// 				'value' => ['approved','programmed'],
// 			'compare' => 'NOT IN'
// 			)
// 		),
// 	);
// 	$args2 = array(
// 		'post_type' => 'film',
// 		//get all posts
// 		'posts_per_page' => -1,
// 		//return an array of post IDs
// 		'fields' => 'ids',
// 		//now check for posts that have a _status that is not 'approved'
// 		'meta_query' => array(
// 			array(
// 				'key' => '_status',
// 				'compare' => 'NOT EXISTS'
// 			)
// 		),
// 	);
	
// 	//print_r(getcurrentedition_func(array('display' => 'previous')));
	
// 	$args3 = array(
// 		'post_type' => 'film',
// 		//get all posts
// 		'posts_per_page' => -1,
// 		//return an array of post IDs
// 		'fields' => 'ids',
// 		//now check for posts that have a _status that is not previous
// 	    'tax_query' => array(
// 	        array (
// 	            'taxonomy' => 'edition',
// 	            'field' => 'slug',
// 	            'terms' => getcurrentedition_func(array('display' => 'previous')),
// 			)
// 		),
// 	);      
		
// 		//now get the posts
//       $excluded_ids = get_posts($args);
//       $excluded_ids2 = get_posts($args2);
//       $excluded_ids3 = get_posts($args3);
//       $excluded_ids = array_merge($excluded_ids, $excluded_ids2, $excluded_ids3);
//       $excluded_ids = array_unique($excluded_ids);

//       //add these post IDs to the 'post__not_in' query parameter
//       $query->set('post__not_in', $excluded_ids);

// }
// // Desactive en WAFFTWO 
// //add_action('pre_get_posts','exclude_posts_with_meta_from_orderby');




/**
 * WAFFTWO FCTS 
 * 
 */

// // filtrage des recherches -> limite aux articles publiés, aux pages et à un custom post type
// // WAFFTWO > Desactiver car bcp bcp trop lent > on privilégie la recherche via SearchWP
// function waff_advanced_search( $query ) {
//     if ( $query->is_search && !is_admin() ) {
// 		// First, only search onto some post_types
// 		$query->set( 'post_type', array( 'post', 'page', 'film', 'jury' ) );
		
// 		// Then filter films by 
// 		$args_meta = array(
// 			'post_type' => 'film',
// 			//get all posts
// 			'posts_per_page' => -1,
// 			//return an array of post IDs
// 			'fields' => 'ids',
// 			//now check for posts that have a _status that is not 'approved'
// 			'meta_query' => array(
// 				'relation' => 'OR',
// 				// If not approved or programmed
// 				array(
// 					'key'   => '_status',
// 					'value' => ['approved','programmed'],
// 					'compare' => 'NOT IN'
// 				),
// 				// If no status
// 				array(
// 					'key' => '_status',
// 					'compare' => 'NOT EXISTS'
// 				),
// 			),
// 		);
		
// 		$args_tax = array(
// 			'post_type' => 'film',
// 			//get all posts
// 			'posts_per_page' => -1,
// 			//return an array of post IDs
// 			'fields' => 'ids',
// 			//now check for posts that have a _status that is not previous
// 			'tax_query' => array(
// 				array (
// 					'taxonomy' => 'edition',
// 					'field' => 'slug',
// 					'terms' => getcurrentedition_func(array('display' => 'previous')),
// 				),
// 			),
// 		);

// 		//now get the posts
// 		$excluded_ids 	= get_posts($args_meta);
// 		$excluded_ids2 	= get_posts($args_tax);
// 		$excluded_ids 	= array_merge($excluded_ids, $excluded_ids2);
// 		$excluded_ids 	= array_unique($excluded_ids);	

// 		//add these post IDs to the 'post__not_in' query parameter
// 		$query->set('post__not_in', $excluded_ids);

// 		// Set posts_per_page 
// 		$query->set('posts_per_page', 20);

//     }
//     return $query;
// }
// // ajout du filtrage sur le hook 'pre_get_post'
// //add_filter( 'pre_get_posts', 'waff_advanced_search' );


/**
 * Types and views filters 
 * =================================================================
 * =================================================================
 * =================================================================
 * =================================================================
 */


/*
	Toolset VIEWS
	Ajoute un filtre par critere status dans la vue Tous les films : 34906
*/
/*add_filter( 'wpv_filter_query', 'custom_orderby_criteria_34906',10,2 );
function custom_orderby_criteria_34906( $query_args ,$view_settings ) {
	if (isset($view_settings['view_id']) && $view_settings['view_id'] == 34906 ) {
			$index = 0;
			if(isset($query_args['meta_query'])){
			$index = count($query_args['meta_query'])+1;
			}
			$query_args['meta_query'][$index] =   array(
				'relation' => 'AND',
				array(
						'key' => '_status',
						'value' => ['approved','programmed'],
						'compare' => 'IN',
				)
			);
	}
return $query_args;
}*/

/*
	Toolset VIEWS
	Ajoute un filtre par critere status dans la vue Tous les films : 34906 ( tous-les-films )
	CE FILTRE EST UTILISE POUR LES LISTE DE FILMS MAIS PAS POUR LES PROJECTIONS par exemple 
	En effet, un film doit forcement etre programmé ou approuvé pour avoir été projeté  
*/
add_filter( 'wpv_filter_query', 'custom_metaquery_criteria_status',20,2 );
function custom_metaquery_criteria_status( $query_args, $view_settings ) {
	//echo $view_settings['view_id'];
	if (isset($view_settings['view_id']) ) {
		if ( in_array($view_settings['view_id'], array(34906) ) ) { 
			$index = 0;
			if(isset($query_args['meta_query'])){
			$index = count($query_args['meta_query'])+1;
			}
			$query_args['meta_query'][$index] =   array(
				'relation' => 'AND',
				array(
						'key' => '_status',
						'value' => ['approved','programmed'],
						'compare' => 'IN',
				)
			);
		}
	}
	return $query_args;
}




//add_action( 'pre_get_posts', 'taxonomy_section_sort_order'); 
// function taxonomy_section_sort_order($query) {
	
// 	if (is_admin()){
//         return;
// 	}
	
// 	if(is_post_type_archive('film')):
// 		// Meta : f-french-operating-title
// 		$query->set( 'meta_key', 'wpcf-f-french-operating-title' );
// 		// Meta : f-order-title

// 		//Set the order ASC or DESC
// 		$query->set( 'order', 'DESC' );
// 		//Set the orderby
// 		$query->set( 'orderby', array('meta_value', 'title') );

// 	endif;    
// };



/*
	Toolset VIEWS
	Ajoute un filtre par critere status dans la vue Related / film : 44397
	02/2021
	N'EST PAS UTILISE
*/
//add_filter( 'wpv_filter_query', 'custom_orderby_criteria_44397',20,3 );
function custom_orderby_criteria_44397( $view_args, $view_settings, $view_id ) {
	echo $view_id; // Géré dans Views avec promote puis ordre aléatoire 
	if (isset($view_settings['view_id']) && $view_settings['view_id'] == 44397 ) {

        $view_args['meta_query'] = array(
            'relation'  => 'OR',
            'promoted'        	=> array(
				'key' => 'wpcf-f-promote',
				'value' => 1,
				'compare' => '=',
			),
            /*'notpromoted'    	=> array(
				'key' => 'wpcf-f-promote',
				'value' => 1,
				'compare' => '!=',
            ),*/
            'notpromoted'      	=> array(
                'key'     => 'wpcf-f-film-poster',
                'compare' => 'EXISTS',
			),
        );
 
        $view_args['orderby'] = array(
                'promoted'        	=> 'ASC',
                'notpromoted'    	=> 'ASC',
		);
		
	}

	return $view_args;
}






/*
	Toolset VIEWS
	Ajouter un orderby custom meta field dans la vue Projection Dans l'heure.
*/


//add_filter( 'wpv_filter_query_post_process', 'projections_sort_query_func', 40, 3 );
// Fonctionne mais apparement il y a un autre tri qui casse celui-ci
function projections_sort_query_func( $query, $view_settings, $view_id ) {
  if ( !empty( $query->posts ) &&  $view_id == 34922) {
    usort($query->posts, "calculated_time_cmp");
  }
  return $query;
}
function calculated_time_cmp($a, $b)
{

  	$a_date = get_post_meta($a->ID, 'wpcf-p-date', true);
  	$a_date = new DateTime();
	$a_date->setTimestamp($a_date);

  	$b_date = get_post_meta($b->ID, 'wpcf-p-date', true);
  	$b_date = new DateTime();
	$b_date->setTimestamp($b_date);

  	$a = get_post_meta($a->ID, 'wpcf-p-start-and-stop-time', true);
  	$b = get_post_meta($b->ID, 'wpcf-p-start-and-stop-time', true);
  	//$a_time = new DateTime($a['begin']);
  	//$b_time = new DateTime($b['begin']);

	$a_merge = new DateTime($a_date->format('Y-m-d') . ' ' . $a["begin"] . ':00' );
	$b_merge = new DateTime($b_date->format('Y-m-d') . ' ' . $b["begin"] . ':00' );

//	wp_die(var_dump(
//		array(
//			array($a['begin'], $a_date->format('Y-m-d'), $a_merge, $a_merge->getTimestamp()),
//			array($b['begin'], $b_date->format('Y-m-d'), $b_merge, $b_merge->getTimestamp())
//		)
//	));


	//$a_merge = $a_date->setTime($a_time->format('H'), $a_time->format('i'), $a_time->format('s'));
	//$b_merge = $b_date->setTime($b_time->format('H'), $b_time->format('i'), $b_time->format('s'));


		//	wp_die(var_dump($a_merge->getTimestamp()));

    if ($a_merge->getTimestamp() == $b_merge->getTimestamp() ) { return 0; }
    return ($a_merge->getTimestamp() < $b_merge->getTimestamp() ) ? -1 : 1;
}

//add_filter( 'wpv_filter_query', 'custom_orderby_criteria_34922',10,2 );
function custom_orderby_criteria_34922( $query_args ,$view_settings ) {
 if (isset($view_settings['view_id']) && $view_settings['view_id'] == 34922 ) {

 			//print_r($query_args);

             $index = 0;
             if(isset($query_args['meta_query'])){
                $index = count($query_args['meta_query'])+1;
             }
             $query_args['meta_query'][$index] =   array(
                        'relation' => 'AND',
                        array(
                                'key' => 'wpcf-p-start-and-stop-time__begin',
                                'value' => '10',
                                'compare' => 'LIKE',
                        )
        );

}
return $query_args;
}

//add_filter( 'wpv_filter_query', 'custom_orderby_criteria_25296',10,3 );
function custom_orderby_criteria_25296( $query_args ,$view_settings, $view_id ) {
	// 25296 : projections-jour : Projections /jour FIFAM 
	if (isset($view_settings['view_id']) && $view_settings['view_id'] == 25296 ) {
		//print_r($query_args);
		$query_args['orderby'] = 'wpcf-p-start-and-stop-time__begin';
		$query_args['order'] = 'ASC';
		return $query_args;
	}

	// 667 : projections-jour : Projections /jour DINARD 
	if (isset($view_settings['view_id']) && $view_settings['view_id'] == 667 ) {
		//print_r($query_args);
		$query_args['orderby'] = 'wpcf-p-start-and-stop-time__begin';
		$query_args['order'] = 'ASC';
		return $query_args;
	}

//	// 659 : projections-film-jour-ajax : Projections / film / jour ( AJAX ) DINARD
// 	// if (isset($view_settings['view_id']) && $view_settings['view_id'] == 659 ) {
// 	// 	//print_r($query_args);
// 	// 	$query_args['orderby'] = 'wpcf-p-start-and-stop-time__begin';
// 	// 	$query_args['order'] = 'ASC';
// 	// 	return $query_args;
// 	// }
// }

}

//add_filter( 'wpv_filter_query', 'custom_orderby_criteria_47006',10,3 );
function custom_orderby_criteria_47006( $query_args ,$view_settings, $view_id ) {
	if (isset($view_settings['view_id']) && $view_settings['view_id'] == 47006 ) {
		//print_r($query_args);
	   $query_args['orderby'] = 'wpcf-p-start-and-stop-time__begin';
	   $query_args['order'] = 'ASC';
	   return $query_args;
	}
   }

/* 
	Ajouter des query vars 
*/
function theme_query_vars( $qvars ) {
    $qvars[] = 'noedition';
    return $qvars;
}
add_filter( 'query_vars', 'theme_query_vars' );

/**
	?? Utilisé en tant que fct dans views ?? >> probablement si un film a du contenu ancien dans le RTE 
*/

function wpv_conditional_post_has_content($type, $object) {
    $return = 0;
    if ( $type == 'film' ) {
        if ( empty( $object->post_content ) ) {
            $return = 0;
        } else {
            $return = count($object->post_content);
        }
    }
    return $return;
}

add_shortcode( 'waff_theme', function(){
    return WAFF_THEME;
});

/**
 * Register connections shortcode
 *
 * <a href="https://toolset.com/forums/users/att/" rel="nofollow" tabindex="79">@att</a> (string) relationship : post relationship slug
 * @return count of connected posts
 */
// Toolset > 2.3 OK 
add_shortcode( 'connections', function( $atts = [] ){

    // provide defaults
    $atts = shortcode_atts(
        array(
            'relationship'      	=>   'film',
			'forposttype'      		=>   'projection',
			'identification'      	=>   '', //film_projection
        ),
        $atts
    );

    global $post;
    $count = 0;

    if ( $atts['identification'] != '' ) 
		$relationship = toolset_get_relationship($atts['identification']); //FIFAM / KO DINARD
	else
		$relationship = toolset_get_relationship(array($atts['relationship'],$atts['forposttype'])); // DINARD
	
		// error_log('##');
		// error_log(print_r($relationship, true));

		// [slug] => film_projection
		// [labels] => Array
		// 	(
		// 		[plural] => Films Projections
		// 		[singular] => Film Projection
		// 	)
	
		// [roles] => Array
		// 	(
		// 		[parent] => Array
		// 			(
		// 				[domain] => posts
		// 				[types] => Array
		// 					(
		// 						[0] => film
		// 					)
	
		// 			)
	
		// 		[child] => Array
		// 			(
		// 				[domain] => posts
		// 				[types] => Array
		// 					(
		// 						[0] => projection
		// 					)
	
		// 			)
	
		// 	)
	
		// [cardinality] => Array
		// 	(
		// 		[limits] => Array
		// 			(
		// 				[parent] => Array
		// 					(
		// 						[min] => 0
		// 						[max] => 1
		// 					)
	
		// 				[child] => Array
		// 					(
		// 						[min] => 0
		// 						[max] => -1
		// 					)
	
		// 			)
	
		// 		[type] => one-to-many
		// 	)
	
		// [origin] => standard

    if ( $relationship ) {

        $parent = $relationship['roles']['parent']['types'][0];
        $child = $relationship['roles']['child']['types'][0];
        $type = $post->post_type;

        $origin = ( $parent == $type ) ? 'parent' : 'child';

        // Get connected posts // FIFAM Old < 2.3 
		// $connections = toolset_get_related_posts( $post->ID, array($atts['relationship'],$atts['forposttype']), $origin, 9999, 0, array(), 'post_id', 'other', null, 'ASC', true, $count );
		
        // Get connected posts // DINARD New > 2.3 
		$connections = toolset_get_related_posts( 
			// get posts related to this one
			$post->ID, 
		 
			// Relationship between the posts
			array($atts['relationship'],$atts['forposttype']), 
		 
			// Additional arguments
			[
				// Get posts where $writer is the parent in given relationship.
				// This is mandatory because we're passing just a single $writer post as the first parameter.
				'query_by_role' => $origin, 
		 
				// pagination
				'limit' => 9999, 
				'offset' => 0, 
		 
				// How was his surname, again…? Search posts by the string "Harry".
				// 'args' => [ 's' => 'Harry' ], 
		 
				'role_to_return' => 'all',
				'return' => 'post_id',
				'need_found_rows' => true,
			]
		);
		 

		error_log('###');
		error_log(print_r($connections, true));

    }
	error_log(print_r($count, true));

    return $count;
});


function date_now_shortcode($atts) {
	return time();
}
add_shortcode('datenow', 'date_now_shortcode');

// Add Shortcode [capitalize]
function func_capitalize($atts, $content='') {
	return ucwords(strtolower(do_shortcode($content)));
}
add_shortcode('capitalize', 'func_capitalize');

// Add Shortcode [do_markdown]content
function func_do_markdown($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
			'raw' => false
        ),
        $atts
    );

	//  ['\\\*\\\*(\\\w.+?)\\\*\\\*', {'bold': true}], // **value**
	//  ['\\\*(\\\w.+?)\\\*', {'italics': true}], // *value*
	$content = do_shortcode($content);
	$content = str_replace('###SPACE###', '', $content); // Je ne sais pas d'ou cela provient mais probablement types
	$content = str_replace('&#8217;', '\'', $content); // Gerer les carateres spéciaux de word 
	$content = str_replace('&#8220;', '&ldquo;', $content);
	$content = str_replace('&#8221;', '&rdquo;', $content);
	$content = str_replace('&#8211;', '&ndash;', $content);
	$content = str_replace('&#8230;', '&hellip;', $content); // Gérer les ...
	$content = str_replace(': #', ': &num;', $content); // Gérer les attrributs <span style="color: #...."
	
	if ( $atts['raw'] != true ) {
		$content = htmlentities($content);
		$patterns = array('/\*\*\*(\w.+?)\*\*\*/', '/\*\*(\w.+?)\*\*/', '/\*(\w.+?)\*/', '/\#\#([^#]+?)\#\#/', '/\#([^#]+?)\#/'); // '/\#\#([^#(SPACE)]+?)\#\#/', '/\#([^#(SPACE)]+?)\#/' // '/\#\#(\w.+?)\#\#/', '/\#(\w.+?)\#/'
		$replacements = array('<span class="label">$1</span>', '<strong>$1</strong>', '<em>$1</em>', '<span class="paragraph-huge">$1</span>', '<span class="paragraph-small">$1</span>');
		ksort($patterns);
			ksort($replacements);
		$content = preg_replace($patterns, $replacements, $content);
	} else {
		$content = htmlentities($content);
		$patterns = array('/\*\*\*(\w.+?)\*\*\*/', '/\*\*(\w.+?)\*\*/', '/\*(\w.+?)\*/', '/\#\#([^#]+?)\#\#/', '/\#([^#]+?)\#/'); // '/\#\#([^#(SPACE)]+?)\#\#/', '/\#([^#(SPACE)]+?)\#/' // '/\#\#(\w.+?)\#\#/', '/\#(\w.+?)\#/'
		$replacements = array('$1', '$1', '$1', '$1', '$1');
		ksort($patterns);
			ksort($replacements);
		$content = preg_replace($patterns, $replacements, $content);
	}
	return html_entity_decode($content);
}
add_shortcode( 'do_markdown', 'func_do_markdown' );

// Add Shortcode [strip_tags] to remove <span> or <p> to content
function func_clean_tags($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
        ),
        $atts
    );
	$content = strip_tags(do_shortcode($content), array('strong','em','a','i','b','u'));
  	return $content;
}
add_shortcode( 'clean_tags', 'func_clean_tags' );


// check_if_content
function func_if_content($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
        ),
        $atts
	);
	$return = false;
	$content = do_shortcode($content);
	if ( strlen(strip_tags($content)) > 0 ) $return = true;
  	return $return;
}
add_shortcode( 'check_if_content', 'func_if_content' ); // Ne fonctionne plus.. je ne sais pas pourquoi > le SC ne ferme pas ex : return content...[/check_if_content]

function func_has_content($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
        ),
        $atts
	);
	$return = false;
	$content = wpv_do_shortcode($content);
	if ( strip_tags($content) != '' ) $return = true;
  	return $return;
}
add_shortcode( 'has_content', 'func_has_content' );

function wpv_has_content() { // directly in a conditionnal 
	return "DEGUG";
}

// Has french title > used for views 
function func_has_french_title($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
            'filmid' =>   '',
        ),
        $atts
    );
	
	$return = false;
  	$meta = get_post_meta($atts['filmid'], 'wpcf-f-french-operating-title', true);
	if ( $meta != '') $return = true;
  	return $return;
}
add_shortcode( 'has_french_title', 'func_has_french_title' );

function func_has_parent_field($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
            'field' =>   '',
            'item' =>   '',
        ),
        $atts
	);
	$return = false;
	$item = func_set_get(array('action' => 'get', 'attribute' => 'items'), ''); // For Film card template
	$override_item = esc_attr($atts['item']);
	if ( $override_item != '' ) $item = $override_item; // For Projections view
	if ( $item != '' )
		if ( $atts['field'] == 'post-title')
			$return .= do_shortcode( '[wpv-post-title item="'. $item .'"]' );
		elseif ( $atts['field'] == 'post-url' ) 
			$return .= do_shortcode( '[wpv-post-url item="'. $item .'"]' );
		else
			$return .= '$('.  $atts['field'] .').item('. $item .')';
	else 
		if ( $atts['field'] == 'post-title')
			$return .= do_shortcode( '[wpv-post-title]' );
		elseif ( $atts['field'] == 'post-url' ) 
			$return .= do_shortcode( '[wpv-post-url]' );
		else
			$return .= '$('.  $atts['field'] .')';
		
  	return $return;
}
add_shortcode( 'has_parent_field', 'func_has_parent_field' );

// Add Shortcode [get_parent_term_id termslug=""]
function func_get_parent_term_ID($atts, $content='') {
	global $current_edition_id;
	global $current_edition_parent_term_id;

    // provide defaults
    $atts = shortcode_atts(
        array(
            'termslug' =>   '',
            'termtaxonomy' =>   '',
        ),
        $atts
    );

	$termParent = 0;
	$termslug = $atts['termslug'];
	$termtaxonomy = $atts['termtaxonomy'];

	if(!empty($termslug) && $termslug !='' ) {
		$term = get_term_by('slug', $termslug, $termtaxonomy);
		$termid = $term->term_id;
		$parenttermid = $term->parent;
		$termParent = ($parenttermid == 0 || $parenttermid == $current_edition_parent_term_id) ? $termid : $parenttermid;
	}

  	return $termParent;
}
add_shortcode( 'get_parent_term_id', 'func_get_parent_term_ID' );


// Add Shortcode [get_term_meta termid="" termmeta=""]
function func_get_term_meta_data($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
            'termid' =>   '',
            'termslug' =>   '',
            'termtaxonomy' =>   '',
            'termmeta' =>   '',
            'return' =>   '',
        ),
        $atts
    );
    
	$meta = 0;
	$termid = $atts['termid'];
	$termmeta = $atts['termmeta'];

	//RAW
	if(!empty($termid) && $termid !='' ) {
		$meta = get_term_meta($termid, $termmeta, true);
	}

	$termslug = $atts['termslug'];
	$termtaxonomy = $atts['termtaxonomy'];


	if(!empty($termslug) && $termslug !='' ) {
		$term = get_term_by('slug', $termslug, $termtaxonomy);
		$termid = $term->term_id;
		$meta = get_term_meta($termid, $termmeta, true);
	}
	
	if ( $atts['return'] == 'boolean') {
		if ( $meta != '') {
			$meta = true;
		} else {
			$meta = false;
		}
	}

  	return $meta;
}
add_shortcode( 'get_term_meta', 'func_get_term_meta_data' );

// Add Shortcode [get_terms post_id="" taxonomy="" term=""]
  function get_post_terms( $atts ) {
    $vals =  shortcode_atts(
      array(
        'post_id' => '',
        'taxonomy' => '',
        'term' => '',
      ), $atts );
    // Code
    $terms = get_the_terms( $vals["post_id"], $vals["taxonomy"] );
    $draught_links = array();
    if (is_array($terms)){
      foreach ( $terms as $term ) {
        $draught_links[] = $term->$vals['term'];
      }
      return implode (", ", $draught_links);
    }
  }
  add_shortcode( 'get_terms', 'get_post_terms' );
  
  

/*
	Get post taxonomies for view but return only one tax
	Same as wpv-post-taxonomy
*/

add_shortcode('wpv-single-post-taxonomy', 'single_post_taxonomies_shortcode_render');
function single_post_taxonomies_shortcode_render($atts) {

	if ( class_exists( 'WPV_wpcf_switch_post_from_attr_id', false ) ) 
		$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);
	else 
		$post_id_atts = null;

 
    extract(
        shortcode_atts( array('format' => '',
                              'type' => 'category',
                              'show' => 'name',
                              'order' => 'asc'
                              ),
                       $atts )
    );
 
    global $wplogger;
     
    $out = '';
    if (empty($atts['type'])) {
        return $out;
    }
    $types = explode(',', @strval($atts['type']));
    if (empty($types)) {
        return $out;
    } else {
		$types = array_map( 'trim', $types );
		$types = array_map( 'sanitize_text_field', $types );
	}
     
    global $post;
    $separator = !empty($atts['separator']) ? @strval($atts['separator']) : ', ';
    $out_terms = array();
    foreach ($types as $taxonomy_slug) {
        $terms = get_the_terms($post->ID, $taxonomy_slug);
        if ( $terms && !is_wp_error( $terms )) {

			foreach ( $terms as $term ) {
				// Adjust the term in case WPML is not set to auto-adjust IDs.
				$term = get_term( apply_filters( 'wpml_object_id', $term->term_id, $taxonomy_slug, true ) );
				// Check whether the filter and the core function return the right object type.
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				switch ( $atts['format'] ) {
					case 'text':// DEPRECATED at 1.9, keep for backwards compatibility
						$text = $term->name;
						switch ( $atts['show'] ) {
							case 'description':
								$text = $term->description;
								break;
							case 'count':
								$text = $term->count;
								break;
							case 'slug':
								$text = $term->slug;
								break;
						}
						$out_terms[ $term->name ] = $text;
						break;
					case 'name':
						$out_terms[ $term->name ] = $term->name;
						break;
					case 'description':
						$out_terms[ $term->name ] = $term->description;
						break;
					case 'count':
						$out_terms[ $term->name ] = $term->count;
						break;
					case 'slug':
						$out_terms[ $term->name ] = urldecode( $term->slug );
						break;
					case 'url':
						$term_link = get_term_link( $term, $taxonomy_slug );
						$out_terms[ $term->name ] = $term_link;
						break;
					default:
						$term_link = get_term_link( $term, $taxonomy_slug );
						$text = $term->name;
						switch ( $atts['show'] ) {
							case 'description':
								$text = $term->description;
								break;
							case 'count':
								$text = $term->count;
								break;
							case 'slug':
								$text = $term->slug;
								break;
						}
						$out_terms[ $term->name ] = '<a href="' . $term_link . '">' . $text . '</a>';
						break;
				}
			}

        }
    }

    if (!empty($out_terms)) {
        if ($atts['order'] == 'asc') {
            ksort($out_terms);
        } elseif ($atts['order'] == 'desc') {
            ksort($out_terms);
            $out_terms = array_reverse($out_terms);
        }
		$out = array_shift($out_terms); // display-only-one-category
        //$out = implode($separator, $out_terms);
    }
    
    apply_filters('wpv_shortcode_debug','wpv-post-taxonomy', json_encode($atts), '', 'Data received from cache', $out);
    
    return $out;
}


/*
	Get post taxonomies for view but return only if in current edition 
	Same as wpv-post-taxonomy
*/

add_shortcode('wpv-post-taxonomy-in-edition', 'current_edition_post_taxonomies_shortcode_render');
function current_edition_post_taxonomies_shortcode_render($atts) {

	if ( class_exists( 'WPV_wpcf_switch_post_from_attr_id', false ) ) 
		$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);
	else 
		$post_id_atts = null;

    extract(
        shortcode_atts( array('format' => '',
                              'type' => 'category',
                              'show' => 'name',
                              'order' => 'asc'
                              ),
                       $atts )
    );
 
    global $wplogger, $current_edition_id;
     
    $out = '';
    if (empty($atts['type'])) {
        return $out;
    }
    $types = explode(',', @strval($atts['type']));
    if (empty($types)) {
        return $out;
    } else {
		$types = array_map( 'trim', $types );
		$types = array_map( 'sanitize_text_field', $types );
	}

    global $post;
    $separator = !empty($atts['separator']) ? @strval($atts['separator']) : ', ';
    $out_terms = array();
    foreach ($types as $taxonomy_slug) {
        $terms = get_the_terms($post->ID, $taxonomy_slug);
        if ( $terms && !is_wp_error( $terms )) {

			foreach ( $terms as $term ) {
				// Adjust the term in case WPML is not set to auto-adjust IDs.
				$term = get_term( apply_filters( 'wpml_object_id', $term->term_id, $taxonomy_slug, true ) );
				// Check whether the filter and the core function return the right object type.
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				
				// Get the selected edition of term
				$term_selected_edition = get_term_meta($term->term_id,'wpcf-select-edition',true); 
				// Continue if section not in current edition 
				if ( $term_selected_edition != $current_edition_id ) {
					continue;
				}

				switch ( $atts['format'] ) {
					case 'text':// DEPRECATED at 1.9, keep for backwards compatibility
						$text = $term->name;
						switch ( $atts['show'] ) {
							case 'description':
								$text = $term->description;
								break;
							case 'count':
								$text = $term->count;
								break;
							case 'slug':
								$text = $term->slug;
								break;
						}
						$out_terms[ $term->name ] = $text;
						break;
					case 'name':
						$out_terms[ $term->name ] = $term->name;
						break;
					case 'description':
						$out_terms[ $term->name ] = $term->description;
						break;
					case 'count':
						$out_terms[ $term->name ] = $term->count;
						break;
					case 'slug':
						$out_terms[ $term->name ] = urldecode( $term->slug );
						break;
					case 'url':
						$term_link = get_term_link( $term, $taxonomy_slug );
						$out_terms[ $term->name ] = $term_link;
						break;
					default:
						$term_link = get_term_link( $term, $taxonomy_slug );
						$text = $term->name;
						switch ( $atts['show'] ) {
							case 'description':
								$text = $term->description;
								break;
							case 'count':
								$text = $term->count;
								break;
							case 'slug':
								$text = $term->slug;
								break;
						}
						$out_terms[ $term->name ] = '<a href="' . $term_link . '">' . $text . '</a>';
						break;
				}
			}

        }
    }

    if (!empty($out_terms)) {
        if ($atts['order'] == 'asc') {
            ksort($out_terms);
        } elseif ($atts['order'] == 'desc') {
            ksort($out_terms);
            $out_terms = array_reverse($out_terms);
        }
        $out = implode($separator, $out_terms);
    }
    
    apply_filters('wpv_shortcode_debug','wpv-post-taxonomy', json_encode($atts), '', 'Data received from cache', $out);
    
    return $out;
}

/*
	Get post taxonomies for view but return only if in current edition 
	Same as wpv-taxonomy-post-count
*/

add_shortcode('wpv-taxonomy-post-count-in-edition', 'current_edition_taxonomy_post_count_render');
function current_edition_taxonomy_post_count_render($atts, $content = null) {
    global $WP_Views, $current_edition_id;
	//https://toolset.com/forums/topic/how-do-i-retrieve-term_id-from-currently-queried-terms-in-taxonomy-view/

	extract(
        shortcode_atts( array(
			'term_id' => "",
			),
			$atts )
    );

	if ( $atts['term_id'] != ''){
		$t_id = $atts['term_id'];
	} else {
		$t = $WP_Views->get_current_taxonomy_term(); 
		$t_id = $t->term_id;
	}
	// print_r("termid:::", $atts['term_id']);
	// print_r("t:::", $t_id);	

	$term = get_term( $t_id );
	// print_r("term::", $term);	

	if ( null === $term ) {
		return '';
	}
	$taxonomy = $term->taxonomy;

	$args = array(
		'post_type' => 'jury',
		'posts_per_page' => -1,
		'tax_query' => array(
			'relation' => 'AND',
			array (
				'taxonomy' 	=> 'edition',
				'field' 	=> 'term_id',
				'terms' 	=> array($current_edition_id),
			),
			array (
				'taxonomy' 	=> $taxonomy,
				'field' 	=> 'term_id',
				'terms' 	=> array($t_id),
			)
		),
		//return an array of post IDs
		'fields' => 'ids',
	);      
    $jurys = get_posts($args);
	// print_r($jurys);

	$out = count($jurys);

	apply_filters( 'wpv_shortcode_debug', 'wpv-taxonomy-post-count-in-edition', json_encode( $atts ), '', 'Data received from cache.', $out );

	return $out;
}

/*
	Grant media upload access and rights for visitors for a CRED form
*/

add_shortcode( 'show_if_user_logged_out', 'show_if_user_logged_out_func');
function show_if_user_logged_out_func($attr, $content='') {
	// provide defaults
	$atts = shortcode_atts(
		array(
			//
		),
		$atts
	);
	$content = do_shortcode($content);
	if ( !is_user_logged_in() )
		return $content;
}

add_shortcode( 'show_if_user_logged_in', 'show_if_user_logged_in_func');
function show_if_user_logged_in_func($attr, $content='') {
	// provide defaults
	$atts = shortcode_atts(
		array(
			//
		),
		$atts
	);
	$content = do_shortcode($content);
	if ( is_user_logged_in() )
		return $content;
}

add_shortcode( 'get_cred_urlparam', 'cred_field_urlparam_email');
function cred_field_urlparam_email($attr) {
	// Used in = 51735 ( cred ) 
	// Used as = [cred_user_form form="editer-un-utilisateur" user="[get_cred_urlparam]"]
	// Used for = https://www.fifam.fr/modification-dun-utilisateur/?email=contact@1015productions.fr
	 $user = get_user_by( 'email', esc_attr($_GET['email']) );
	// print_r($user);
     return $user->ID;
}

add_action( 'admin_init','reset_guest_caps', 9 );
function reset_guest_caps(){
    global $current_user, $wpcf_access;
    if(isset($_GET['formid']) && $_GET['formid']==13226&&$current_user->ID==0)
    {
        $wpcf_access->settings=array();
    }
}

function filterGetTermArgs($args, $taxonomies) {
    global $typenow;

    if ($typenow == 'film') {
        // check whether we're currently filtering selected taxonomy
        if (implode('', $taxonomies) == 'section') {
            //Add categories term ID that you want to show
            $cats = array(145); // List of category(term ID) that you want to add as an array

            if (empty($cats))
                $args['include'] = array(99999999); // no available categories
            else
                $args['include'] = $cats; //It will only show the category that you mentioned in above array
        }
    }

    return $args;
}

/*
	SC for types wafftwo 
*/
// Add Shortcode [waff_gallery] 
function func_waff_gallery($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
			'field' => '',
			'size' => 'film-gallery-image',
			'blockquote-title' => 'L\'avis du festival',
			'blockquote-footer' => 'Annouchka De Andrade, Directrice artistique',
			'display' => 'masonry' //gallery
        ),
        $atts
    );
	$content = do_shortcode($content);

	$field = types_render_field( $atts['field'], array(
		//'output' 	=> 'raw',
		//'url' 	=> 'true',
		'size' 		=> $atts['size'], 
		//'show_name' => true, 
		'alt'		=> '%%DESCRIPTION%%', 
		'title' 	=> '%%CAPTION%%', //%%TITLE%%
		'class' 	=> 'img-fluid h-600-px',
		'style' 	=> 'object-fit: cover; width: 100%;', //height: 600px; 
		'separator'	=> '&', //KO
		)
	);
	$fields =  explode('&', $field);

	$displays = array(
		'col-12 col-lg-9 media',
		'col-12 col-lg-3 offset-lg-0 media',
		'col-12 col-lg-5 offset-lg-5 media',
		'col-12 col-lg-3 offset-lg-2 media',
		'col-12 col-lg-7 offset-lg-5 media',
		'col-12 col-lg-5 offset-lg-0 media',
		'col-12 col-lg-3 offset-lg-0 media',
		'col-12 col-lg-4 offset-lg-8 media',
		'col-12 col-lg-8 offset-lg-0 media',
		'col-12 col-lg-5 offset-lg-3 media',
		'col-12 col-lg-3 offset-lg-8 media',
		'col-12 col-lg-7 offset-lg-5 media',
		'col-12 col-lg-5 offset-lg-0 media',
		'col-12 col-lg-3 offset-lg-9 media',
		'col-12 col-lg-9 offset-lg-3 media',
		'col-12 col-lg-3 offset-lg-0 media',
		'col-12 col-lg-5 offset-lg-5 media',
		'col-12 col-lg-3 offset-lg-2 media',
		'col-12 col-lg-7 offset-lg-5 media',
		'col-12 col-lg-5 offset-lg-0 media',
		'col-12 col-lg-3 offset-lg-0 media',
		'col-12 col-lg-4 offset-lg-8 media',
		'col-12 col-lg-8 offset-lg-0 media',
		'col-12 col-lg-5 offset-lg-3 media',
		'col-12 col-lg-3 offset-lg-8 media',
		'col-12 col-lg-7 offset-lg-5 media',
		'col-12 col-lg-5 offset-lg-0 media',
		'col-12 col-lg-3 offset-lg-0 media',
	);

	// ------ Loop display gallery 

	if ( $atts['display'] == 'masonry' ) :
	// Before
	$footer = explode(',', func_do_markdown(array(), $atts['blockquote-footer']) );
	$output = '<div class="row medias contrast--light align-items-center row-cols-4 pt-4 pb-0 pt-sm-8 pb-sm-8 g-0 f-w">
				<div class="col-12 col-lg-3 excerpt p-gutter-l pt-4 pb-4 pr-4 --pl-4">';

	if ( $content != '' ) {
		$output .= sprintf(
				'	<span class="subline mb-3">%s</span>
					<blockquote class="blockquote mt-2">
						%s
						%s
					</blockquote>
			',
			esc_html($atts['blockquote-title']),
			$content,
			(($footer[0] != '')?sprintf('<footer class="blockquote-footer">%s,<cite>%s</cite></footer>', $footer[0], $footer[1]):'')
		);
	}
	$output .= '</div>';
	
	//Loop 
	foreach( $fields as $index => $field ) {
		preg_match_all('/<img.*?alt="(.*?)".*>/', $field, $alt);
		preg_match_all('/<img.*?title="(.*?)".*>/', $field, $title);
		$alt = implode($alt[1]);
		$title = implode($title[1]);
		
		$output .= sprintf(
			'<div class="%s" id="%s">
				<figure>
					<picture class="lazy">%s</picture>
					%s
				</figure>				
			</div>',
			$displays[$index],
			$index,
			$field,
			(($alt != '' || $title != '')?sprintf('<figcaption><strong>%s</strong> %s</figcaption>', esc_html($title), esc_html($alt)):'')
		);
	}

	// After
	$output .= '</div>';

	elseif ( $atts['display'] == 'gallery' ) :
	// ------ Loop display gallery 

	// Before
	$footer = explode(',', func_do_markdown(array(), $atts['blockquote-footer']) );
	$output = '<div class="row medias contrast--light align-items-center row-cols-4 pt-8 pb-8 g-0 f-w">
				<div class="col-12 col-lg-12 excerpt pt-4 pb-4 pr-4 pl-4 text-center">';

	if ( $content != '' ) {
		$output .= sprintf(
				'	<span class="subline mb-2 text-action-1">%s</span>
					<blockquote class="blockquote mt-0 text-action-1 w-50 m-auto lead">
						%s
						%s
					</blockquote>
			',
			$atts['blockquote-title'],
			$content,
			(($footer[0] != '')?sprintf('<footer class="blockquote-footer">%s,<cite>%s</cite></footer>', $footer[0], $footer[1]):'')
		);
	}
	$output .= '</div>';

	//Loop 
	$figures = array();
	foreach( $fields as $index => $field ) {
		preg_match_all('/<img.*?alt="(.*?)".*>/', $field, $alt);
		preg_match_all('/<img.*?title="(.*?)".*>/', $field, $title);
		preg_match_all('/<img.*?src="(.*?)".*>/', $field, $src);
		$alt = implode($alt[1]);
		$title = implode($title[1]);
		$src = implode($src[1]);
		
		$figures[] = sprintf(
			'	<figure id="%s">
					<picture class="lazy" data-fancybox="gallery" data-src="%s">%s</picture>
					%s
				</figure>
			',
			$index,
			$src,
			($index > 0 && count($fields) > 2 )?preg_replace('/(<img.*?class=")([^"]*)(.*?>)/', '$1attachment-film-gallery-image img-fluid h-300-px$3', $field):$field, //$field,
			(($alt != '' || $title != '')?sprintf('<figcaption><strong>%s</strong> %s</figcaption>', esc_html($title), esc_html($alt)):'')
		);
	}

	// Render 
	$output .= '
	<div class="col-12">
		<div class="row g-0">
	';
	if ( count($figures) > 1 ) :
	$output .= '
		<div class="col-12 col-md-4 order-1 order-md-0">
				<div class="d-flex flex-column">
					<div class="'.((count($figures) > 2)?'h-75':'h-100').'">'.$figures[1].'</div>
	';
		if ( count($figures) > 2 ) :
			$output .= '
						<div class="h-25">
							<div class="row g-0">
								<div class="col">'.$figures[2].'</div>
			';
								if ( count($figures) > 3 ) :
									$output .= '
									<div class="col position-relative">'.$figures[3];
									if ( count($figures) > 4 ) :
										$output .= '<a data-fancybox-trigger="gallery" href="javascript:;" class="bg-transparent-action-1 position-absolute w-100 h-100 top-0 start-0 impact text-light d-flex align-items-center justify-content-center heading-2" id="">+'.(count($figures)-4);
										foreach ( array_slice($figures, 4) as $figure ) {
											$output .= '<div class="visually-hidden">'. $figure . '</div>';
										}
										$output .= '</a>';
									endif;
									$output .= '</div>';
								endif;
			$output .= '
							</div>
					</div>
			';
		endif;
		$output .= '
				</div>
			</div>
		';
	endif;
	$output .= '
			<div class="'.((count($figures) > 1)?'col-12 col-md-8 order-0 order-md-1':'col-12').'">
				'.$figures[0].'
			</div>
		</div>
	</div>
	';

	// After
	$output .= '</div>';

	else : 

	$output = 'Please choose a display mode.';

	endif;


  	return $output;
}
add_shortcode( 'waff_gallery', 'func_waff_gallery' );

function func_linebreaks_to_list($atts, $content='') {
    // provide defaults
    $atts = shortcode_atts(
        array(
			'ul_class' => '',
			'li_class' => '',
        ),
        $atts
	);
	$output = '';
	//$content = do_shortcode($content);
	$content = strip_tags(do_shortcode($content), array('span','strong','em','a','i','b','u'));
	$contents = explode("\n", $content);
	$notempty = 0;
	foreach($contents as $k => $v)
		if( !empty($v) || (string)$v != '' )
			$notempty++;

	if($notempty > 0) {
		$output .= '<ul class="'.$atts['ul_class'].'">';
		foreach($contents as $content) {
			if ( $content != '')
				$output .= '<li class="'.$atts['li_class'].'">' . func_do_markdown(array(), $content) . '</li>';
		}
		$output .= '</ul>';
	} 
	return $output;
}
add_shortcode( 'linebreaks_to_list', 'func_linebreaks_to_list' );


function func_get_formats($atts, $content='') {
	global $current_edition_id;

    // provide defaults
    $atts = shortcode_atts(
        array(
			'field' 			=> 'f-available-formats',
			'output' 			=> 'language',
			'for_projection' 	=> 0,
        ),
        $atts
	);
	$output = '<!-- func_get_formats : outputs -->';
	//$content = do_shortcode($content);

	// Get post id & type
	$postid = get_the_ID();
	$posttype = get_post_type();

	/**
	 * Get data for output style : language, icon, format
	 */
	if ( ($atts['output'] == 'language' || $atts['output'] == 'icon' || $atts['output'] == 'format') ) {
		//echo '@@@formats';

		$has_kdm 				= false;
		$has_withoutdialogue 	= false;
		$has_3D					= false;
		$has_vostfr 			= false;

		$formats				= array();
		$format_informations	= array();
		$vos					= array();
		$vost					= array();
		$sound_format			= array();

		/*
			[format] => DCP
			[kdm] => 1
			[format_information] => 2K
			[vo] => en
			[vostfr] => 1
			[vost] => fr
			[without_dialogue] => 1
			[sound_format] => 5.1
		*/
		$fields = get_post_meta( $postid, 'wpcf-'.$atts['field'], false); // true = no array 
		foreach($fields as $index => $field) {
			// print_r($field);
			if ( $field['format'] != '' && $field['format'] != 'N/A' )
				$formats[] = $field['format'];
			if ( $field['format_information'] != '' && $field['format_information'] != 'N/A' )
				$format_informations[] = $field['format_information'];
			if ( $field['vo'] != '' && $field['vo'] != 'N/A' )
				$vos[] = $field['vo'];
			if ( $field['vost'] != '' && $field['vost'] != 'N/A' )
				$vost[] = $field['vost'];
			if ( $field['sound_format'] != '' && $field['sound_format'] != 'N/A' )
				$sound_format[] = $field['sound_format'];
			if ( $field['kdm'] == 1 ) 
				$has_kdm = true;
			if ( $field['without_dialogue'] == 1 ) 
				$has_withoutdialogue = true;
			if ( $field['format_information'] == "3D" ) 
				$has_3D = true;
			if ( $field['vostfr'] == 1 ) 
				$has_vostfr = true;
		}
	}

	/**
	 * Get data for output style : tag
	 */
	if ( ($atts['output'] == 'tag' || $atts['output'] == 'inline-tag') ) {
		//echo '@@@tag';

		// Check if has_projections
		$has_projections = false;
		$relationship		= 'film';
		$forposttype 		= 'projection';
		$identification 	= 'film_projection'; // > Toolset 3.0
		$count_connections 	= 0;
		if ( $identification != '' ) 
			$has_relationship = toolset_get_relationship( $identification ); // DINARD
		else
			$has_relationship = toolset_get_relationship( array( $relationship, $forposttype ) ); //FIFAM / KO DINARD // FIFAM Old < 2.3 
		// echo('##has_relationship<pre>'); print_r($has_relationship);  echo('</pre>');

		if ( $has_relationship ) {
			$parent = $has_relationship['roles']['parent']['types'][0];
			$child = $has_relationship['roles']['child']['types'][0];

			$origin = ( $parent == $posttype ) ? 'parent' : 'child';
			$returning = ( $parent == $posttype ) ? 'child' : 'parent';

			if (get_post_type($postid) == 'film') {
				// echo('####From a projection, film list');
				$origin = 'parent';
				$returning = 'child';	
			}
			
			//Get connected posts // FIFAM Old < 2.3 
			// $connections = toolset_get_related_posts( $postid, array($relationship,$forposttype), $origin, 9999, 0, array(), 'post_id', 'other', null, 'ASC', true, $count_connections );
			// if ( !empty($connections) )  $has_projections = true;
			// echo('##posttype in loop<pre>'); print_r($posttype); echo('</pre>');
			// echo('##ID in loop<pre>'); print_r($postid); echo('</pre>');
			// echo('##ID queried <pre>'); print_r(get_queried_object_id()); echo('</pre>');
			// echo('##posttype queried <pre>'); print_r(get_post_type(get_queried_object_id())); echo('</pre>');

			// Get connected film // Toolset 3.0 DINARD New > 2.3 
			$connections = toolset_get_related_posts(
				$postid, //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
				$identification, //relationship
				array(
					'query_by_role' => $origin, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
					'role_to_return' => $returning, // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
					'return' => 'post_id', // Return array of IDs (post_id) or post objects (post_object)
					'limit' => 999, // Max number of results
					'offset' => 0, // Starting from
					// 'orderby' => 'title', 
					// 'order' => 'ASC',
					'need_found_rows' => false, // also return count of results
					'args' => null // Array for adding meta queries etc.
				)
			);
			// echo('##connections film<pre>'); print_r($connections); echo('</pre>');

			// Get connected films 
			$connections_films = toolset_get_related_posts(
				$postid, //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
				'films-projection', //relationship
				array(
					'query_by_role' => $origin, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
					'role_to_return' => $returning, // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
					'return' => 'post_id', // Return array of IDs (post_id) or post objects (post_object)
					'limit' => 999, // Max number of results
					'offset' => 0, // Starting from
					// 'orderby' => 'title', 
					// 'order' => 'ASC',
					'need_found_rows' => false, // also return count of results
					'args' => null // Array for adding meta queries etc.
				)
			);
			// echo('##connections films<pre>'); print_r($connections_films); echo('</pre>');
			if ( empty($connections) && !empty($connections_films) ) $connections = $connections_films;

			if ( get_post_type(get_queried_object_id()) == 'projection')
				$connections = array(get_queried_object_id()); // If we came from a projection single page > then request all the meta from projection and not from relations. 


			// Finally, we just have to check if we have connections 
			if ( !empty($connections) && count($connections) > 0 ) {
				$has_projections 		= true;
				$has_youngpublic		= false;
				$has_highlight			= false;
				$has_guest				= false;
				$has_debate				= false; //ADDED #43
				$has_tag				= false;
				$has_program			= false; //ADDED #43
				$guests					= array();
				$guest_names			= array();

				foreach($connections as $index => $connection) {
					// echo('##connection film<pre>'); print_r($connection); echo('</pre>' . get_post_meta( $connection, 'wpcf-p-is-guest', true));
					// Don't show metas if not the correct edition ( [0] = a projection cannot have multiple editions )
					$edition = get_the_terms($connection, 'edition');
					if ( $edition[0]->term_id != $current_edition_id) continue;
					// Get metas
					if ( get_post_meta( $connection, 'wpcf-p-young-public', true) == 1 ) // true = no array
						$has_youngpublic = true;
					if ( get_post_meta( $connection, 'wpcf-p-highlights', true) == 1 ) // true = no array
						$has_highlight = true;
					if ( get_post_meta( $connection, 'wpcf-p-is-guest', true) == 1 ) // true = no array
						$has_guest = true;
					if ( get_post_meta( $connection, 'wpcf-p-is-debate', true) == 1 ) // true = no array //ADDED #43
						$has_debate = true;
					if ( get_post_meta( $connection, 'wpcf-p-tag', true) != '' ) // true = no array
						$has_tag = true;
					if ( $has_guest == true ) {
						//$contacts 	= types_render_field('p-e-guest-contact', array('item' => $connection, 'output' => 'raw')); // List
						//$contact 		= get_post_meta( $connection, 'wpcf-p-e-guest-contact', true); // Unique 
						$contacts 		= get_post_meta( $connection, 'wpcf-p-e-guest-contact', false); // Array 
						$guest_names[] 	= get_post_meta( $connection, 'wpcf-p-guest-name', true); // Unique 
						foreach( $contacts as $contact ) {
							$lastname 	= get_post_meta( $contact, 'wpcf-c-name', true);
							$firstname 	= get_post_meta( $contact, 'wpcf-c-firstname', true);
							$surname 	= get_post_meta( $contact, 'wpcf-c-surname', true);

							$lastname 	= (strlen($lastname)>20)?substr($lastname,0,17).'...':$lastname;
							$firstname 	= (strlen($firstname)>20)?substr($firstname,0,17).'...':$firstname;
							$surname 	= (strlen($surname)>30)?substr($surname,0,27).'...':$surname;

							$guests[] 	= (($surname != '')?$surname:$firstname.' '.$lastname); 
						}
						// DEBUG
						// print_r($guests);
						// print_r($guest_names);
					}
					if ( $has_tag == true )
						$tag 		= types_render_field( 'p-tag', array('id' => $connection ) );
					if ( !empty($connections_films) && count($connections_films) > 0 ) // true = no array
						$has_program = true;
				}
			}
		}
	}

	/**
	 * Output
	 */
	if ( $atts['output'] == 'language' ) {
		if ( !empty($vos) ) 					$output .= '<span class="language-item link-disabled">VO('.implode(',', $vos).')</span>';
		if ($has_vostfr == true) 				$output .= '<span class="language-item link-disabled">VOSTFR</span>';
		if ( !empty($vost) ) 					$output .= '<span class="language-item link-disabled">VOST('.implode(',', $vost).')</span>';

	} else if ( $atts['output'] == 'icon' ) {
		if ($has_withoutdialogue == true) 		$output .= '<span class="icon-item link-disabled"><i class="icon icon-without mr-1"></i></span>';
		if ($has_3D == true) 					$output .= '<span class="icon-item link-disabled"><i class="icon icon-3d mr-1"></i></span>';
	} else if ( $atts['output'] == 'format' ) {
		if ( !empty($formats) ) 				$output .= '<span class="format-item link-disabled">'.implode(', ', $formats).'</span>';
		if ( !empty($format_informations) ) 	$output .= '<span class="format-item link-disabled">'.implode(', ', $format_informations).'</span>';
		//$output .= 'has_projections : '.$has_projections;
		//$output .= 'count_connections : '.$count_connections;
		//$output .= 'has_kdm : '.$has_kdm;
	} else if ( $atts['output'] == 'tag' ) {
		// if ($has_program == true) 
 		if ($has_highlight == true) 
			$output .= sprintf('<span class="badge text-wrap color-black text-dark text-dark text-left text-uppercase normal" style="max-width: 5rem;">
				<i class="icon icon-sun float-left float-start mr-1 f-14"></i>
				<small>Temps-<strong class="bold">fort</strong></small>
			</span>');
		if ($has_youngpublic == true) 
			$output .= sprintf('<span class="badge text-wrap color-black text-dark text-left text-uppercase normal" style="max-width: 6rem;">
				<i class="icon icon-young float-left float-start mr-1 f-14"></i>
				<small>Parents-<strong class="bold">enfants</strong></small>
			</span>');
		if ($has_guest == true) 
			$output .= sprintf('<span class="badge text-wrap color-black text-dark text-left text-uppercase normal" style="max-width: 7rem;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<em>En présence de</em> ・ %s">
				<i class="icon icon-guest float-left float-start mr-1 f-14"></i>
				<small>Avec <span class="screen-reader-text">invité</span><strong class="bold">%s</strong></small>
			</span>',
			implode(',', $guests),
			(!empty($guest_names))?implode(', ', $guest_names):implode(', ', $guests),
			);
		if ($has_debate == true) 
			$output .= sprintf('<span class="badge text-wrap color-black text-dark text-dark text-left text-uppercase normal" style="max-width: 5rem;">
				<i class="icon icon-mic float-left float-start mr-1 f-14"></i>
				<small>Avec <strong class="bold">débat</strong></small>
			</span>');
		// No projection tag needed here
	} else if ( $atts['output'] == 'inline-tag' ) {
		if ($has_program == true) 
			$output .= sprintf('<i class="icon icon-play"></i> Dans un programme <span class="me-2"></span>');
		if ($has_highlight == true) 
			$output .= sprintf('<i class="icon icon-sun"></i> Temps-fort <span class="me-2"></span>');
		if ($has_youngpublic == true) 
			$output .= sprintf('<i class="icon icon-young"></i> Parents-enfants <span class="me-2"></span>');
		if ($has_guest == true) 
			$output .= sprintf('<span data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<em>En présence de</em> ・ %s"><i class="icon icon-guest"></i> En présence de <span class="subline">%s</span></span> <span class="me-2"></span>',
			implode(',', $guests),
			(!empty($guest_names))?implode(', ', $guest_names):implode(', ', $guests),
			);
		if ($has_debate == true) 
			$output .= sprintf('<i class="icon icon-mic"></i> Avec débat <span class="me-2"></span>');
		if ($has_tag == true) 
			$output .= sprintf('<span class="text-danger"><i class="icon icon-warning text-danger"></i> %s</span> <span class="me-2"></span>',
			print_r($tag, true)
			);
	} else {
		$output = 'Error: no rendering output chosen';
	}

	// print_r($output);
	return $output;
}
add_shortcode( 'get_formats', 'func_get_formats' );


function func_get_programs($atts, $content='', $dontknowwhat, $p_id = null, $f_id = null) {
	// Output 
	$atts = shortcode_atts(
        array(
			'output' 		=> 'array',
			'p-id' 			=> null,
			'f-id' 			=> null,
        ),
        $atts
	);
	$output = '<!-- func_get_programs : outputs -->';
	$films = [];
	$orders = [];

	// P or F id 
	$p_id = ($atts['p-id'] != null)?intval(esc_attr($atts['p-id'])):intval($p_id);
	$f_id = ($atts['f-id'] != null)?intval(esc_attr($atts['f-id'])):intval($f_id);

	// echo('##get_the_ID<b>'); echo get_the_ID(); echo('</b>');
	// echo('##p-id<b>'); echo intval(esc_attr($atts['p-id'])); echo('</b>');
	// echo('##f-id<b>'); echo intval(esc_attr($atts['f-id'])); echo('</b>');
	// echo('##p_id<b>'); echo $p_id; echo('</b>');
	// echo('##f_id<b>'); echo $f_id; echo('</b>');

	// Get post id & type
	$postid = ( $p_id !== 0 || $f_id !== 0 )?(($p_id !== 0 )?$p_id:$f_id):get_the_ID();

	// echo('##postid<b>'); echo $postid; echo('</b>');
	// error_log($postid);

	$posttype = ($p_id)?'film':get_post_type();
	$posttype = ($f_id)?'projection':get_post_type();

	// echo('##posttype<b>'); echo $posttype; echo('</b>');
	// error_log($posttype);

	// Check if has_programs
	$relationship		= 'film';
	$forposttype 		= 'projection';
	$identification 	= 'films-projection'; // > Toolset 3.0
	if ( $identification != '' ) 
		$has_relationship = toolset_get_relationship( $identification ); // DINARD / FIFAM > 3.0
	else
		$has_relationship = toolset_get_relationship( array( $relationship, $forposttype ) ); //FIFAM / KO DINARD // FIFAM Old < 2.3 
	//echo('##has_relationship<pre>'); print_r($has_relationship);  echo('</pre>');
	//error_log($has_relationship);

	if ( $has_relationship ) {
		$parent = $has_relationship['roles']['parent']['types'][0];
		$child = $has_relationship['roles']['child']['types'][0];
		
		$origin = ( $parent == $posttype ) ? 'child' : 'parent';
		$returning = ( $parent == $posttype ) ? 'parent' : 'child';
		
		if ( $p_id !== 0 ) {
			$origin = ( $parent == $posttype ) ? 'child' : 'parent';
			$returning = ( $parent == $posttype ) ? 'parent' : 'child';
			// $origin = ( $parent == $posttype ) ? 'parent' : 'child';
			// $returning = ( $parent == $posttype ) ? 'child' : 'parent';
		}

		if ( $f_id !== 0 || $posttype == 'film' ) {
			// $origin = ( $parent == $posttype ) ? 'child' : 'parent';
			// $returning = ( $parent == $posttype ) ? 'parent' : 'child';
			$origin = ( $parent == $posttype ) ? 'parent' : 'child';
			$returning = ( $parent == $posttype ) ? 'child' : 'parent';
		}


		// if ( $posttype == 'film' ) {
		// 	$origin = "parent";
		// 	$returning = "child";
		// }

		// echo('##postid<pre>'); print_r($postid); echo('</pre>');
		// echo('##posttype<pre>'); print_r($posttype); echo('</pre>');
		// echo('##parent<pre>'); print_r($parent); echo('</pre>');
		// echo('##origin<pre>'); print_r($origin); echo('</pre>');
		// echo('##returning<pre>'); print_r($returning); echo('</pre>');

		// $origin = "child";
		// $returning = "child";
		
		//Get connected posts // FIFAM Old < 2.3 
		// $connections = toolset_get_related_posts( $postid, array($relationship,$forposttype), $origin, 9999, 0, array(), 'post_id', 'other', null, 'ASC', true, $count_connections );
		// if ( !empty($connections) )  $has_projections = true;

		// Get connected posts // DINARD New > 2.3 
		$connections = toolset_get_related_posts(
			$postid, //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
			$identification, //relationship
			array(
				'query_by_role' => $returning, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
				'role_to_return' => 'all', // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
				'return' => 'post_object', // Return array of IDs (post_id) or post objects (post_object)
				'limit' => 999, // Max number of results
				'offset' => 0, // Starting from
				// 'orderby' => 'title', 
				// 'order' => 'ASC',
				'need_found_rows' => false, // also return count of results
				'args' => null // Array for adding meta queries etc.
			)
		);
		//retrieve parent post ID from a one to many relationsship when knowing the child post-ID
		//$connections = toolset_get_related_posts( $postid, $identification, 'parent' );
		// echo('##connections<pre>'); print_r($connections); echo('</pre>');
		// echo('##connections count<pre>'); print_r(count($connections)); echo('</pre>');

		// Finally, we just have to check if we have connections 
		if ( !empty($connections) && count($connections) > 0 ) {
			// $has_projections 		= true;
			// $has_youngpublic		= false;
			// $has_highlight			= false;
			// $has_guest				= false;
			// $has_debate				= false; //ADDED #43
			// $has_tag				= false;
			// $guests					= array();

			foreach($connections as $index => $connection) {
				$films[] = $connection["parent"]->ID; // IF [parent][post_type] => film >>>>> print this is a program but don't print titles 
 
				$orders[] = get_post_meta($connection["intermediary"]->ID, "wpcf-f-p-film-order", true) ;
			}
		}
	}

	// echo('##output<pre>'); print_r($output);  echo('</pre>');
	// echo('##orders<pre>'); print_r($orders);  echo('</pre>');

	// Then orders by wpcf-f-p-film-order
	$filmsordered = [];
	foreach($films as $k => $v) 
		$filmsordered[$orders[$k]] = $v;
	ksort($filmsordered);

	if ( $atts['output'] == 'count' ) {
		return count($connections);
	} else if ( $atts['output'] == 'titles' ) {
		if ( count($connections) > 0 ) 
			$output .= render_programs($filmsordered, true)[0];
			// 	foreach ( $filmsordered as $k => $film ) {
			// 		$french_title = get_post_meta($film, 'wpcf-f-french-operating-title', true);
			// 		$output .= '<span class="program_title"><a href="' . get_permalink($film) . '" title="">' . (($french_title != '')?$french_title:get_the_title($film)) . '</a></span>';
			// 		$output .= render_programs($filmsordered);
			// 		if ($k !== array_key_last($filmsordered)) $output .= ' <span class="program_sep length">+</span> ';
			// 	}
		else
			$output .="<i data-bs-toggle=\"tooltip\" data-bs-html=\"true\" title=\"Ce film est proposé dans un programme\" class=\"icon icon-play mr-1 f-12 d-inline-block\"></i>";
		return $output;
	} else if ( $atts['output'] == 'images' ) {
		$output .= '<div class="slick-film-card w-100 h-280-px">';
		foreach ( $filmsordered as $k => $film ) {
			//post-featured-image-s-x2
			//class = --w-100 h-280-px fit-image
			$f_featured_img = get_the_post_thumbnail( $film, 'post-featured-image-s', array( 'class' => '--w-100 h-280-px fit-image' )); // #43 reduced size from post-featured-image-s-x2
			$output .= sprintf('<figure>
				%s
			</figure>', $f_featured_img);
		}
		$output .= '</div>';
		return $output;
	} else if ( $atts['output'] == 'array' ) {
		return $filmsordered;
	} else {
		return null;
	}

}
add_shortcode( 'get_programs', 'func_get_programs' );

function render_programs($films, $title_only = false) {
	$program = '';
	$program_length = 0;
	foreach( $films as $k => $p_f_id ) {
		$p_f_title 						= (( get_the_title($p_f_id) )?get_the_title($p_f_id):'');
		$p_f_french_operating_title 	= get_post_meta( $p_f_id, 'wpcf-f-french-operating-title', true );
		$p_f_movie_length 				= get_post_meta( $p_f_id, 'wpcf-f-movie-length', true );
		
		// if ( !$title_only ) {
		$p_f_author 					= get_post_meta( $p_f_id, 'wpcf-f-author', true ); //#43
		$p_f_production_year 			= get_post_meta( $p_f_id, 'wpcf-f-production-year', true ); //#43
		//$p_f_available_formats 			= get_post_meta( $p_f_id, 'wpcf-f-available-formats', true ); //#43

		$p_f_country_ 					= get_post_meta( $p_f_id, 'wpcf-f-country', true ); //#43
		$p_f_co_production_country_ 	= get_post_meta( $p_f_id, 'wpcf-f-co-production-country', true ); //#43
		$p_f_country 					= types_render_field( 'f-country', array('item' => $p_f_id) ); //#43
		$p_f_co_production_country 		= types_render_field( 'f-co-production-country', array('item' => $p_f_id) ); //#43
		// }

		$p_f_premiere_ 					= get_post_meta( $p_f_id, 'wpcf-f-premiere', true ); //#43
		$p_f_catalog_tag_ 				= get_post_meta( $p_f_id, 'wpcf-f-catalog-tag', true ); //#43
		$p_f_premiere 					= types_render_field( 'f-premiere', array('item' => $p_f_id) ); //#43
		$p_f_catalog_tag 				= types_render_field( 'f-catalog-tag', array('item' => $p_f_id) ); //#43

		// $p_f_poster 					= get_post_meta( $p_f_id, 'wpcf-f-film-poster', true ); //#43
		// $p_f_poster_id 					= WaffTwo\Core\waff_get_image_id_by_url($p_f_poster);
		// $p_f_poster_url 				= wp_get_attachment_image_url( $p_f_poster_id, "film-poster" ); // OK
		// $p_f_poster_img 				= wp_get_attachment_image( $p_f_poster_id, "film-poster", "", array( "class" => "img-responsive" ) ); // OK

		// $p_f_featured_img 				= get_the_post_thumbnail( $p_f_id, 'film-poster');
		// $p_f_poster_img					= ( $p_f_poster != '') ? $p_f_poster_img : $p_f_featured_img;

		// Get terms
		$p_f_sections 					= get_the_terms( $p_f_id, 'section' );
		$html_p_f_section = '';
		$last_p_f_section_color = '';
		$last_p_f_section = '';
		if (is_array($p_f_sections)) foreach($p_f_sections as $p_f_section) {
			$p_f_section_color = get_term_meta( $p_f_section->term_id, 'wpcf-s-color', true );
			if ( $p_f_section_color != '' ) $last_p_f_section_color = $p_f_section_color;
			if ( $p_f_section_color != '' ) $last_p_f_section = $p_f_section;
			$p_f_section_edition = get_term_meta( $p_f_section->term_id, 'wpcf-select-edition', true );
			if ( $current_edition_id == $p_f_section_edition ) // Only current edition sections
			$html_p_f_section .= sprintf('<a href="%s" %s class="dot-section" data-bs-toggle="tooltip" data-bs-container=".modal" data-bs-title="%s" data-bs-original-title="" title="">•</a>',
			get_term_link($p_f_section->slug, 'section'),
			(( $p_f_section_color != '' )?'style="color: '.$p_f_section_color.';"':''),
			$p_f_section->name
			);
		}

		$tooltip = ((( $p_f_author != '' )?'DE '.$p_f_author['lastname'].' '.$p_f_author['firstname']:'') . (( $p_f_country != '' )?' ・ '.$p_f_country:'') . (( $p_f_co_production_country != '' )?' ・ '.$p_f_co_production_country:'') . (( $p_f_production_year != '' )?' ・ ('.$p_f_production_year.')':''));
		$program .= sprintf('
				<span class="last_f_section_color" %s>
					<a href="%s" class="title %s" %s>%s</a>
					%s
				</span>
				%s
				%s
				%s
				%s
				%s
				<!-- Program sep-->
				%s',
		(( $last_p_f_section_color != '' )?'style="color: '.$last_p_f_section_color.';"':''),
		(( $p_f_title != '' )?get_permalink( $p_f_id ):get_permalink( $p_f_id )),
		(( $p_f_title != '' )?'text-link btn-link disabled':'text-link'),
		(( $last_p_f_section_color != '' )?'style="color: '.$last_p_f_section_color.';"':'') . (($title_only)?' data-bs-toggle="tooltip" data-bs-container=".part_one" data-bs-title="' . esc_html($tooltip) . '" data-bs-placement="top"':''),
		esc_html(( $p_f_french_operating_title != '' && !$title_only )?$p_f_french_operating_title.' ('.$p_f_title.')':$p_f_title),
		(( $p_f_author != '' && !$title_only )?'&nbsp;<span class="article">DE</span>&nbsp;<span class="director">'.$p_f_author['lastname'].' '.$p_f_author['firstname'].'</span>':''),
		(( $p_f_country != '' && !$title_only )?'・ <span class="country">'.$p_f_country.'</span>':''),
		(( $p_f_co_production_country != '' && !$title_only )?'・ <span class="co_production_country">'.$p_f_co_production_country.'</span>':''),
		(( $p_f_production_year != '' && !$title_only )?'・ <span class="year muted">'.$p_f_production_year.'</span>':''),
		(( $p_f_movie_length != '' )?((!$title_only)?'・ ':'').'<span class="length">'.$p_f_movie_length.'\'</span>':''),
		$html_p_f_section,
		// Program sep 
		( $k < (count($films)) )?' <span class="sep display h5 bold op-5">+</span> ':'',
		);
		$program_length +=  (int) $p_f_movie_length;
	} // End foreach
	return array($program, $program_length, $last_p_f_section); 
}


/* Remove Views Wraps output for slick sliders 
https://toolset.com/forums/topic/the-div-that-wraps-the-view-is-bloated/
*/
add_filter( 'wpv_filter_wpv_view_shortcode_output', 'prefix_clean_view_output', 5, 2 );
function prefix_clean_view_output( $out, $id ) {

	// FIFAM 
	$clean_view_ids = array(
		'25296', // projections-jour : Slick-slides tracks don't need a wrap  = ID IDENTIQUE  dev et dev2
		'25310', // projections-film-jour : Accordion  don't need a wrap  = ID IDENTIQUE  dev et dev2
		'25300', // contenu-contact-id : don't need a wrap = ID IDENTIQUE  dev et dev2
		'25294', // projections-film : Vue toutes les projections / film : don't need a wrap= ID IDENTIQUE  dev et dev2
		'25580', // projection-jour-room : Vue Projection /jour /room : don't need a wrap = ID IDENTIQUE  dev et dev2
		'25304', // projections-du-jour : don't need a wrap
		//'54055', // Vue Related / section : don't need a wrap > ne pas ajouter sinon bug 
	);

	//DINARD 
	if ( defined('WAFF_THEME') && WAFF_THEME == 'DINARD') 
	$clean_view_ids = array(
		'667', // projections-jour : Slick-slides tracks don't need a wrap
		//'', // projections-film-jour : Accordion  don't need a wrap 
		'661', // contenu-contact-id : don't need a wrap
		'662', // projections-film : Vue toutes les projections / film : don't need a wrap
		'676', // projection-jour-room : Vue Projection /jour /room : don't need a wrap 
		//'', // projections-du-jour : don't need a wrap
		//'658', // Vue Related / section : don't need a wrap > ne pas ajouter sinon bug 
	);

	// If this view is inside views to clean, clean it 
	if ( in_array($id, $clean_view_ids) ) {
        $start = strpos( $out, '<!-- wpv-loop-start -->' );
        if ( 
            $start !== false
            && strrpos( $out, '<!-- wpv-loop-end -->', $start ) !== false
        ) {
            $start = $start + strlen( '<!-- wpv-loop-start -->' );
            $out = substr( $out , $start );
            $end = strrpos( $out, '<!-- wpv-loop-end -->' );
            $out = substr( $out, 0, $end );
		}
	}
    // Otherwise normally output
    return $out;
}

/* 
	Toolset ODD EVEN fct / Used on film card
*/ 

function wpv_is_odd($number) {
	//Get the remainder of our number divided by 2.
	$remainder = $number % 2;

	//If the remainder is 0, then it means
	//that the number is even.
	if($remainder == 0){
		return false;
	} else {
		return true;
	}
}


$attributes = array();
function func_set_get($atts, $content){
	global $attributes;
	//print_r(array( $atts, $content, $attributes));
	
    extract(shortcode_atts(array(
		'action' 	=> 'set',
		'attribute' => '',
	), $atts));
	
	// Get 
	if ( $attributes[$atts['attribute']] != '' ) {
		$res = $attributes[$atts['attribute']];
	} else {
		$res = do_shortcode($content); //Fallback default value
	}

	// Set 
    if($action == 'set'){
        $attributes[$atts['attribute']] = do_shortcode($content);
        $res = '';
	}
	
    return $res;
}
add_shortcode('set-get', 'func_set_get');

/**
 * Used in taxonomies archives templates 
 * projections__in is a list of preselected projections from a date to a room 
 */
function get_counts($taxonomy_name = 'section', $taxonomy_id = array(), $projections__in = array()) {

	// echo $taxonomy_name . '<br/>';
	// echo $taxonomy_id. '<br/>';
	// print_r($projections__in);

	// echo gettype($taxonomy_id);
	// if ( !is_array($taxonomy_id) )
	//		$taxonomy_id = [explode(",", $taxonomy_id)];
	// echo gettype($taxonomy_id);

	global $current_edition_id;
	global $post; 

	// Get counts 
	$counts = array();
	$conditionnal_tax_query = array();
	$post__in = array();

	// Section 
	// Room 
	if ( $taxonomy_name === 'section' || $taxonomy_name === 'room')
		$conditionnal_tax_query = array (
			'taxonomy'	=> $taxonomy_name,
			'field' 	=> 'term_id',
			'terms' 	=> $taxonomy_id,
		);

	/**
	 * All films / OK 
	 * All films if no sections
	 */

	// Films Args
	$films_in_section_args = array(
		'post_type' 		=> 'film',
		'posts_per_page' 	=> -1,
		'nopaging' 			=> true,
		'order' 			=> 'DESC',
		'post_status' 		=> 'publish',
		// In edition & in section
		'tax_query' => array(
			'relation' 		=> 'AND',
			array (
				'taxonomy' 	=> 'edition',
				'field' 	=> 'term_id',
				'terms' 	=> array($current_edition_id),
			),
			// Added by a conditionnal
		),
		// Has status required
		'meta_query' => array(
			'relation' 		=> 'AND',
			array(
				'key'   	=> '_status',
				'value' 	=> ['approved','programmed'],
				'compare' 	=> 'IN'
			)
		),
		// Gets IDs
		'fields' => 'ids',
	);

	// Conditionnal ( sections )
	if ( !empty($conditionnal_tax_query) && $taxonomy_name === 'section') 
		$films_in_section_args['tax_query'][] = $conditionnal_tax_query;
	
	// Conditionnal ( sections ) >> TODO FROM DINARD ? This one is better ? 
	// if ( $taxonomy_name === 'section')
	// 	$films_in_section_args['tax_query'][] = array (
	// 		'taxonomy'	=> $taxonomy_name,
	// 		'field' 	=> 'term_id',
	// 		'terms' 	=> $taxonomy_id,
	// 	);

	// Query 
	$films_in_section_query = new WP_Query($films_in_section_args);

	// Results
	$films_in_section_results = $films_in_section_query->posts;

	// Store count
	$counts['films'] = count($films_in_section_results); //found_posts

	//error_log("> 3.0 films_in_section_results");
	//echo( '<b>Film(s) by sections::count</b> ' . print_r(count($films_in_section_results), true) . '<br/>');
	//echo( '<pre> Film(s) by sections::' . print_r($films_in_section_results, true) . '</pre>');

	// Restore original Post Data 
	wp_reset_postdata();
	wp_reset_query();

	/**
	 * All promoted films ( with field )
	 */

	// Promoted args
	$films_promoted_args = array(
		'post_type' 		=> 'film',
		'posts_per_page' 	=> -1,
		'nopaging' 			=> true,
		'order' 			=> 'DESC',
		'post_status' 		=> 'publish',
		// In edition & in section
		'tax_query' => array(
			'relation' 		=> 'AND',
			array (
				'taxonomy' 	=> 'edition',
				'field' 	=> 'term_id',
				'terms' 	=> array($current_edition_id),
			),
			// Added by a conditionnal
		),
		// Has status required
		'meta_query' => array(
			'relation' 		=> 'AND',
			array(
				'key'   	=> '_status',
				'value' 	=> ['approved','programmed'],
				'compare' 	=> 'IN'
			),
			array(
				'key' 		=> 'wpcf-f-promote',
				'compare' 	=> '=',
				'value' 	=> 1,
				'type' 		=> 'NUMERIC'
			),
		),
		// Gets IDs
		'fields' => 'ids',
	);

	// Conditionnal ( sections )
	if ( !empty($conditionnal_tax_query) && $taxonomy_name === 'section' ) 
		$films_promoted_args['tax_query'][] = $conditionnal_tax_query;

	// Conditionnal ( sections ) >> TODO FROM DINARD ? This one is better ? 
	// if ( $taxonomy_name === 'section')
	// 	$films_promoted_args['tax_query'][] = array (
	// 		'taxonomy'	=> $taxonomy_name,
	// 		'field' 	=> 'term_id',
	// 		'terms' 	=> $taxonomy_id,
	// 	);

	// Query 
	$films_promoted_query = new WP_Query($films_promoted_args);

	// Results 
	$films_promoted_results = $films_promoted_query->posts;

	// Store count
	$counts['wpcf-f-promote'] = count($films_promoted_results);

	//echo( '<b>Film(s) promoted by sections::count</b> ' . print_r(count($films_promoted_results), true) . '<br/>');
	//echo( '<pre> Film(s) promoted by sections::' . print_r($films_promoted_results, true) . '</pre>');

	// Restore original Post Data 
	wp_reset_postdata();
	wp_reset_query();

	/**
	 * All projections belongs to a film 
	 */


	// 	/**
	//  * All projections belongs to a film >> DINARD WAY > Old ? 
	//  */

	// // error_log("> 3.0 films_in_section_results");
	// // error_log(print_r($films_in_section_results, true));

	// $belongs_film_projection_ids = array();
	// $relationship = 'film_projection';
	// // get the child posts of the parent post
	// // $belongs_film_projection_ids[] = toolset_get_related_posts(
	// // 	array( 'parent' => $films_in_section_results ), //query_by_elements
	// // 	$relationship, //relationship
	// // 	array(
	// // 		// 'child', // query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
	// // 		100, //limit
	// // 		0, //offset
	// // 		array(), //args
	// // 		'post_id', //return
	// // 		'child' //role_to_return : 'parent' | 'child' | 'intermediary' | 'all'
	// // 	)
	// // );
	// $belongs_film_projection_ids = toolset_get_related_posts(
	// 	array( 'parent' => $films_in_section_results ), //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
	// 	$relationship, //relationship
	// 	array(
	// 		// 'query_by_role' => $origin, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
	// 		'role_to_return' => 'child', // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
	// 		'return' => 'post_id', // Return array of IDs (post_id) or post objects (post_object)
	// 		'limit' => 999, // Max number of results
	// 		'offset' => 0, // Starting from
	// 		// 'orderby' => 'title', 
	// 		// 'order' => 'ASC',
	// 		'need_found_rows' => false, // also return count of results
	// 		'args' => null // Array for adding meta queries etc.
	// 	)
	// );

	// // echo("> 3.0 belongs_film : find parent returns child");
	// // echo( '<pre>' . print_r($belongs_film_projection_ids, true) . '</pre>');
	
	// if ( !is_array($exclude) )
	// 	$post__in = $belongs_film;
	// else 
	// 	$post__in = array_merge( $exclude, $belongs_film);

	// // All projections Query args 
	// $projections_args = array(
	// 	'post_type' 		=> 'projection',
	// 	'posts_per_page' 	=> -1,
	// 	'nopaging' 			=> true,
	// 	'order' 			=> 'DESC',
	// 	'post_status' 		=> 'publish',
	// 		// Have film merged with exclude 
	// 	'post__in'			=> $post__in,
	// 		// In edition & in section
	// 	'tax_query' => array(
	// 		'relation' 		=> 'AND',
	// 		array (
	// 			'taxonomy' 	=> 'edition',
	// 			'field' 	=> 'term_id',
	// 			'terms' 	=> array($current_edition_id),
	// 		),
	// 		// Added by a conditionnal
	// 	),
	// 	// Have films / OLD approach before toolset 3.0 
	// 	// 'meta_query' => array(
	// 	// 	'relation' 		=> 'AND',
	// 	// 	'belongs' => array(
	// 	// 		'compare' 	=> 'IN',
	// 	// 		'key' 		=> '_wpcf_belongs_film_id', // A Revoir maj toolset 
	// 	// 		'value' 	=> $films_in_section_results,
	// 	// 	),
	// 	// ),
	// 	// Have film / NEW approach > NOT WORKING FOR MANY IDs 
	// 	// New toolset_relationships query argument
	// 	// 'toolset_relationships' => array(
	// 	// 	'role' => 'child',
	// 	// 	'related_to' => $films_in_section_results,
	// 	// 	// this will work only with relationships that have existed before the migration
	// 	// 	// if possible, use the relationship slug instead of an array
	// 	// 	//'relationship' => array( 'film', 'projection' ),
	// 	// 	'relationship' => 'film_projection',
	// 	// ),			 
	// 	// Gets IDs
	// 	'fields' => 'ids',
	// );

	// // Conditionnal 
	// if ( $taxonomy_name === 'room')
	// 	$projections_args['tax_query'][] = array (
	// 		'taxonomy'	=> $taxonomy_name,
	// 		'field' 	=> 'term_id',
	// 		'terms' 	=> $taxonomy_id,
	// 	);


	// // Query 
	// $projections_query = new WP_Query($projections_args);

	// // Results 
	// $projections_results = $projections_query->posts;

	// // echo ('<pre>' . print_r($projections_args, true) . '</pre>');
	// // echo ('<pre>' . print_r($projections_results, true) . '</pre>');

	// // Store count
	// $counts['projections'] = count($projections_results);

	// // Restore original Post Data 
	// wp_reset_postdata();
	// wp_reset_query();

	// /**
	//  * All projections not belongs to a film 
	//  */

	// // All projections Query args 
	// $programs_args = array(
	// 	'post_type' 		=> 'projection',
	// 	'posts_per_page' 	=> -1,
	// 	'nopaging' 			=> true,
	// 	'order' 			=> 'DESC',
	// 	'post_status' 		=> 'publish',
	// 	//'post__in'			=> $exclude,
	// 	'post__not_in'		=> $belongs_film_projection_ids, //$projections_results,
	// 	// In edition & in section
	// 	'tax_query' => array(
	// 		'relation' 		=> 'AND',
	// 		array (
	// 			'taxonomy' 	=> 'edition',
	// 			'field' 	=> 'term_id',
	// 			'terms' 	=> array($current_edition_id),
	// 		),
	// 		// Added by a conditionnal
	// 	),
	// 	// Have no films
	// 	// 'meta_query' => array(
	// 	// 	'relation' 		=> 'AND',
	// 	// 	'belongs' => array(
	// 	// 		'compare' 	=> 'NOT EXISTS',
	// 	// 		'key' 		=> '_wpcf_belongs_film_id',
	// 	// 	),
	// 	// ),
	// 	// Gets IDs
	// 	'fields' => 'ids',
	// );

	// // Conditionnal 
	// if ( $taxonomy_name === 'room')
	// 	$programs_args['tax_query'][] = array (
	// 		'taxonomy'	=> $taxonomy_name,
	// 		'field' 	=> 'term_id',
	// 		'terms' 	=> $taxonomy_id,
	// 	);

	// // Query 
	// $programs_query = new WP_Query($programs_args);

	// // Results 
	// $programs_results = $programs_query->posts;

	// // echo ('<pre>' . print_r($programs_args, true) . '</pre>');
	// // echo ('<pre>' . print_r($programs_results, true) . '</pre>');

	// // Store count
	// $counts['programs'] = count($programs_results);

	// // Restore original Post Data 
	// wp_reset_postdata();
	// wp_reset_query();


	// All projections without Query args ( no date, no room, only edition )
	$projections_args = array(
		'post_type' 		=> 'projection',
		'posts_per_page' 	=> -1,
		'nopaging' 			=> true,
		'order' 			=> 'DESC',
		'post_status' 		=> 'publish',
			// In edition
		'tax_query' => array(
			'relation' 		=> 'AND',
			array (
				'taxonomy' 	=> 'edition',
				'field' 	=> 'term_id',
				'terms' 	=> array($current_edition_id),
			),
		),	 
		// Gets IDs
		'fields' => 'ids',
	);

	// Query 
	$projections_query = new WP_Query($projections_args);

	// Results 
	$projections_results = $projections_query->posts;

	// echo ('<b>All Projection(s) ( with and without films )::count</b> ' . print_r(count($projections_results), true) . '<br/>'); // Il va falloir différencier quand on a un exclude ou non 
	// echo ('<pre> ALL Projection(s)::' . print_r($projections_results, true) . '</pre>'); // Il va falloir différencier quand on a un exclude ou non 
	// echo ('<pre> ALL Projection(s)::' . print_r($projections_query, true) . '</pre>'); // Il va falloir différencier quand on a un exclude ou non 

	// Restore original Post Data 
	wp_reset_postdata();
	wp_reset_query();

	/**
	 * Get all projection connected to a film
	 */
	error_log(count($projections_results));
	$belongs_film_projection_ids = array();
	$relationship = 'film_projection';
	$belongs_film_projection_ids = toolset_get_related_posts(
		array( 'parent' => $films_in_section_results ), //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
		$relationship, //relationship
		array(
			// 'query_by_role' => $origin, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
			'role_to_return' => 'child', // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
			'return' => 'post_id', // Return array of IDs (post_id) or post objects (post_object)
			'limit' => count($projections_results)!==0?count($projections_results):-1, // Max number of results
			'offset' => 0, // Starting from
			// 'orderby' => 'title', 
			// 'order' => 'ASC',
			'need_found_rows' => false, // also return count of results
			'args' => array(), // Array for adding meta queries etc.
		)
	);
	// Programs 
	$belongs_programs_projection_ids = toolset_get_related_posts(
		array( 'parent' => $films_in_section_results ), //query_by_elements : single ID or array( 'parent' => $films_in_section_results ), //query_by_elements
		'films-projection', //relationship
		array(
			// 'query_by_role' => $origin, // Origin post role / query_by_role: Name of the element role to query by. This argument is required if a single post is provided in $query_by_elements, and in other cases, it must not be present at all. Accepted values: 'parent' | 'child' | 'intermediary'.
			'role_to_return' => 'child', // Role of posts to return : 'parent' | 'child' | 'intermediary' | 'all'
			'return' => 'post_id', // Return array of IDs (post_id) or post objects (post_object)
			'limit' => count($projections_results)!==0?count($projections_results):-1, // Max number of results
			'offset' => 0, // Starting from
			// 'orderby' => 'title', 
			// 'order' => 'ASC',
			'need_found_rows' => false, // also return count of results
			'args' => array(), // Array for adding meta queries etc.
		)
	);


	/**
	 * Then find all projection not connected to a film 
	 */
	$projections_without_films = array_diff($projections_results, $belongs_film_projection_ids);
	$projections_without_programs = array_diff($projections_results, $belongs_programs_projection_ids);


	// echo("> 3.0 belongs_film : find parent returns child");
	// echo( '<pre> <b>::All films ( in sections )</b>' . print_r($films_in_section_results, true) . '</pre>');
	// echo( '<pre> <b>::Projections__belongs</b>' . print_r($belongs_film_projection_ids, true) . '</pre>'); // Voir pour reduire à l'edition en cours
	// echo( '<b>::Projections__belongs WITH films::count </b>' . print_r(count($belongs_film_projection_ids), true) . '<br/>'); // Voir pour reduire à l'edition en cours
	// echo( '<b>::Projections__belongs WITH programs::count </b>' . print_r(count($belongs_programs_projection_ids), true) . '<br/>'); // Voir pour reduire à l'edition en cours
	// echo( '<b>::Projections__belongs WITHOUT films::count </b>' . print_r(count($projections_without_films), true) . '<br/>'); // Voir pour reduire à l'edition en cours
	// echo( '<b>::Projections__belongs WITHOUT programs::count </b>' . print_r(count($projections_without_programs), true) . '<br/>'); // Voir pour reduire à l'edition en cours
	// echo( '<b>::Projections that ARE EVENTS </b>' . print_r(count(array_diff( $projections__in, array_merge($projections_without_films, $projections_without_programs) )), true) . '<br/>'); // Voir pour reduire à l'edition en cours

	/**
	 * Finally if we have a delected projections array, filter all results 
	 */
	if ( !empty($projections__in) ) {

		// echo("> We have a selected projections__in <br/>");
		$post__in = $projections__in;

		// echo( '<pre> <b>::Projections__in ( projections filtered by date )</b>' . print_r($projections__in, true) . '</pre>');
		// echo( '<pre> <b>::Selected films ( projection WITHOUT films )</b> ' . print_r(array_diff( $projections__in, $belongs_film_projection_ids), true) . '</pre>');
		// echo( '<pre> <b>::Selected programs ( projection WITH films )</b> ' . print_r(array_diff( $projections__in, $projections_without_films), true) . '</pre>');

		// Store count
		$counts['projections'] = count($projections__in);
		$counts['events'] = count(array_diff( $projections__in, array_merge($projections_without_films, $projections_without_programs) ));
		$counts['programs'] = count($belongs_programs_projection_ids);

	} else {

		// echo("> We do not have a selected projections__in, then query on all projections <br/>");

		/**
		 * All projections belongs to a film with query
		 */
		
		$projections_args = array(
			'post_type' 		=> 'projection',
			'posts_per_page' 	=> -1,
			'nopaging' 			=> true,
			'order' 			=> 'DESC',
			'post_status' 		=> 'publish',
				// Have film merged with date  
			'post__in'			=> $belongs_film_projection_ids,
				// In edition & in section
			'tax_query' => array(
				'relation' 		=> 'AND',
				array (
					'taxonomy' 	=> 'edition',
					'field' 	=> 'term_id',
					'terms' 	=> array($current_edition_id),
				),
				// Added by a conditionnal
			),		 
			// Gets IDs
			'fields' => 'ids',
		);

		// Conditionnal ( rooms )
		if ( !empty($conditionnal_tax_query) && $taxonomy_name === 'room') 
			$projections_args['tax_query'][] = $conditionnal_tax_query;

		// // Conditionnal ( rooms ) >> Dinard WAY is it better ? 
		// if ( $taxonomy_name === 'room')
		// $p_args['tax_query'][] = array (
		// 	'taxonomy'	=> $taxonomy_name,
		// 	'field' 	=> 'term_id',
		// 	'terms' 	=> $taxonomy_id,
		// );

		// Query 
		$projections_query = new WP_Query($projections_args);

		// Results 
		$projections_results = $projections_query->posts;

		// echo ('<pre>'. !empty($conditionnal_tax_query). print_r($conditionnal_tax_query, true) . '</pre>');
		// echo ('<pre>' . print_r($projections_args, true) . '</pre>');
		// echo ('<b>Projection(s) WITH films queried::count</b> ' . print_r(count($projections_results), true) . '<br/>');
		// echo ('<pre> Projection(s) WITH films queried::' . print_r($projections_results, true) . '</pre>');

		// Store count
		$counts['projections'] = count($projections_results);

		// Restore original Post Data 
		wp_reset_postdata();
		wp_reset_query();

		$post__in = $projections_results;

		// /**
		//  * All projections not belongs to a film or program with query
		//  */

		$events_args = array(
			'post_type' 		=> 'projection',
			'posts_per_page' 	=> -1,
			'nopaging' 			=> true,
			'order' 			=> 'DESC',
			'post_status' 		=> 'publish',
			'post__in'			=> array_merge($projections_without_films, $projections_without_programs),
			//'post__not_in'		=> array_merge( $projections__in, $belongs_film_projection_ids), //$belongs_film_projection_ids, //$projections_results,
			// In edition & in section
			'tax_query' => array(
				'relation' 		=> 'AND',
				array (
					'taxonomy' 	=> 'edition',
					'field' 	=> 'term_id',
					'terms' 	=> array($current_edition_id),
				),
				// Added by a conditionnal
			),
			// Gets IDs
			'fields' => 'ids',
		);

		// Conditionnal ( rooms )
		if ( !empty($conditionnal_tax_query) && $taxonomy_name === 'room') 
			$events_args['tax_query'][] = $conditionnal_tax_query;

		// Query 
		$events_query = new WP_Query($events_args);

		// Results 
		$events_results = $events_query->posts;

		// echo ('<b>Projection(s) WITHOUT films queried (programs)::count = events</b> ' . print_r(count($events_results), true) . '<br/>');
		// echo ('<pre> Projection(s) WITHOUT films queried(programs):: = events' . print_r($events_results, true) . '</pre>');

		// Store count
		$counts['events'] = count($events_results);

		// Restore original Post Data 
		wp_reset_postdata();
		wp_reset_query();

	}
	
	/**
	 * Projections ( all ) with custom fields 
	 */

	$projection_count_fields = array('wpcf-p-is-guest', 'wpcf-p-young-public', 'wpcf-p-highlights', 'wpcf-p-is-debate');
	foreach ($projection_count_fields as $f) {
		// Query args 
		$p_args = array(
			'post_type' 		=> 'projection',
			'posts_per_page' 	=> -1,
			'nopaging' 			=> true,
			'order' 			=> 'DESC',
			'post_status' 		=> 'publish',
				// Have film merged with exclude 
			'post__in'			=> $post__in,
				// In edition & in section
			'tax_query' => array(
				'relation' 		=> 'AND',
				array (
					'taxonomy' 	=> 'edition',
					'field' 	=> 'term_id',
					'terms' 	=> array($current_edition_id),
				),
				// Added by a conditionnal
			),
			// Have films
			'meta_query' => array(
				'relation' 		=> 'AND',
				'p-compare' => array(
					'key' 		=> $f,
					'compare' 	=> '=',
					'value' 	=> 1,
					'type' 		=> 'NUMERIC'
				)
			),
			// Gets IDs
			'fields' => 'ids',
		);

		// Conditionnal ( rooms )
		if ( !empty($conditionnal_tax_query) && $taxonomy_name === 'room') 
			$p_args['tax_query'][] = $conditionnal_tax_query;

		// Query 
		$p_query = new WP_Query($p_args);

		// Results 
		$p_results = $p_query->posts;

		// echo ('<pre>' . print_r($p_args, true) . '</pre>');
		// echo ('<pre>' . print_r($p_results, true) . '</pre>');

		// Store count
		$counts[$f] = count($p_results);

		if ($f === 'wpcf-p-is-guest') {
			$counts['guests'] = $p_results;
		}

		if ($f === 'wpcf-p-is-debate') {
			$counts['debates'] = $p_results;
		}

		// Restore original Post Data 
		wp_reset_postdata();
		wp_reset_query();
	}

	// echo ('<pre> FINAL COUNTS::' . print_r($counts, true) . '</pre>');
	return $counts;

	/*
		Get Counts old SQL method 
	*/

	/*
	// Get 'wpcf-p-is-guest' // SELECT proj.post_title, film.post_title, proj_meta_key.meta_value
	global $wpdb;
	$sql = <<<SQL
	SELECT film.post_title, film.ID
	FROM wp_posts AS proj, 
	wp_posts AS film, 
	wp_postmeta AS proj_meta_key, 
	wp_postmeta AS proj_meta_film,
	wp_term_relationships AS trs1,
	wp_term_relationships AS trs2
	WHERE ( proj.ID = proj_meta_key.post_id ) 
	AND (proj_meta_film.post_id = proj.ID)
	AND (proj_meta_film.meta_key = '_wpcf_belongs_film_id')
	AND (proj_meta_film.meta_value = film.ID )
	AND ( proj_meta_key.meta_key = 'wpcf-p-is-guest' AND CAST(proj_meta_key.meta_value AS SIGNED) = '1' )
	AND proj.post_type = 'projection' 
	AND ((proj.post_status = 'publish' OR proj.post_status = 'private'))
	AND trs1.object_id = film.ID
	AND trs1.term_taxonomy_id = 219
	AND trs2.object_id = film.ID
	AND trs2.term_taxonomy_id = 221
	GROUP BY proj.ID 
	ORDER BY proj.menu_order, proj.post_date DESC
	SQL;

	$results = $wpdb->get_results($sql);
	echo '<pre><b>is guest::</b>'.print_r($results, true).'</pre>';
	echo '<pre><b>is guest::count</b>'.print_r(count($results), true).'</pre>';

	// Get 'wpcf-p-young-public' // SELECT proj.post_title, film.post_title, proj_meta_key.meta_value
	global $wpdb;
	$sql = <<<SQL
	SELECT film.post_title, film.ID
	FROM wp_posts AS proj, 
	wp_posts AS film, 
	wp_postmeta AS proj_meta_key, 
	wp_postmeta AS proj_meta_film,
	wp_term_relationships AS trs1,
	wp_term_relationships AS trs2
	WHERE ( proj.ID = proj_meta_key.post_id ) 
	AND (proj_meta_film.post_id = proj.ID)
	AND (proj_meta_film.meta_key = '_wpcf_belongs_film_id')
	AND (proj_meta_film.meta_value = film.ID )
	AND ( proj_meta_key.meta_key = 'wpcf-p-young-public' AND CAST(proj_meta_key.meta_value AS SIGNED) = '1' )
	AND proj.post_type = 'projection' 
	AND ((proj.post_status = 'publish' OR proj.post_status = 'private'))
	AND trs1.object_id = film.ID
	AND trs1.term_taxonomy_id = 219
	AND trs2.object_id = film.ID
	AND trs2.term_taxonomy_id = 221
	GROUP BY proj.ID 
	ORDER BY proj.menu_order, proj.post_date DESC
	SQL;

	$results = $wpdb->get_results($sql);
	echo '<pre><b>is young::</b>'.print_r($results, true).'</pre>';
	echo '<pre><b>is young::count</b>'.print_r(count($results), true).'</pre>';

	// Get 'wpcf-p-highlights' // SELECT proj.post_title, film.post_title, proj_meta_key.meta_value
	global $wpdb;
	$sql = <<<SQL
	SELECT film.post_title, film.ID
	FROM wp_posts AS proj, 
	wp_posts AS film, 
	wp_postmeta AS proj_meta_key, 
	wp_postmeta AS proj_meta_film,
	wp_term_relationships AS trs1,
	wp_term_relationships AS trs2
	WHERE ( proj.ID = proj_meta_key.post_id ) 
	AND (proj_meta_film.post_id = proj.ID)
	AND (proj_meta_film.meta_key = '_wpcf_belongs_film_id')
	AND (proj_meta_film.meta_value = film.ID )
	AND ( proj_meta_key.meta_key = 'wpcf-p-highlights' AND CAST(proj_meta_key.meta_value AS SIGNED) = '1' )
	AND proj.post_type = 'projection' 
	AND ((proj.post_status = 'publish' OR proj.post_status = 'private'))
	AND trs1.object_id = film.ID
	AND trs1.term_taxonomy_id = 219
	AND trs2.object_id = film.ID
	AND trs2.term_taxonomy_id = 221
	GROUP BY proj.ID 
	ORDER BY proj.menu_order, proj.post_date DESC
	SQL;

	$results = $wpdb->get_results($sql);
	echo '<pre><b>is highlights::</b>'.print_r($results, true).'</pre>';
	echo '<pre><b>is highlights::count</b>'.print_r(count($results), true).'</pre>';
	*/
}

if (is_admin()) {
   //add_filter('get_terms_args', 'filterGetTermArgs', 10, 2);
}


/**
 * Emails functions 
 * =================================================================
 * =================================================================
 * =================================================================
 * =================================================================
 */



/*
	Wordpress Better emails localization	
*/

function custom_template_vars_replacement( $to_replace ) {
	if ( defined('WAFF_CUSTOM_BRAND')) 
		$to_replace['blog_name'] = WAFF_CUSTOM_BRAND; // From functions.php
	if ( defined('WAFF_CUSTOM_BRAND_DESCRIPTION')) 
		$to_replace['blog_description'] = WAFF_CUSTOM_BRAND_DESCRIPTION; // From functions.php
    return $to_replace;
}
add_filter( 'wpbe_tags', 'custom_template_vars_replacement' );