<?php

include_once( 'sections-colorize.php');


// Get image
/*function scaled_image_path($attachment_id, $size = 'thumbnail') {
    $file = get_attached_file($attachment_id, true);
    if (empty($size) || $size === 'full') {
        // for the original size get_attached_file is fine
        return realpath($file);
    }
    if (! wp_attachment_is_image($attachment_id) ) {
        return false; // the id is not referring to a media
    }
    $info = image_get_intermediate_size($attachment_id, $size);
    if (!is_array($info) || ! isset($info['file'])) {
        return false; // probably a bad size argument
    }

    return realpath(str_replace(wp_basename($file), $info['file'], $file));
}*/

// retrieves the attachment ID from the file URL > BUG avec -scaled depuis WP 4.5  
/*function get_attachment_id( $url ) {
	$attachment_id = 0;
	$dir = wp_upload_dir();
	if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
		$file = basename( $url );
		$query_args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'value'   => $file,
					'compare' => 'LIKE',
					'key'     => '_wp_attachment_metadata',
				),
			)
		);
		$query = new WP_Query( $query_args );
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$meta = wp_get_attachment_metadata( $post_id );
				$original_file       = basename( $meta['file'] );
				$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
				if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
					$attachment_id = $post_id;
					break;
				}
			}
		}
	}
	return $attachment_id;
}*/

// retrieves the attachment ID from the file URL > OK 
function get_attachment_id($image_url) {
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url )); 
        return $attachment[0]; 
}

/* Convert hexdec color string to rgb(a) string */

function hex2rgba($color, $opacity = false) {

	$default = 'rgb(0,0,0)';

	//Return default if no color provided
	if(empty($color))
          return $default;

	//Sanitize $color if "#" is provided
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }

        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }

        //Return rgb(a) color string
        return $output;
}


/**
 * Increases or decreases the brightness of a color by a percentage of the current brightness.
 *
 * @param   string  $hexCode        Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
 * @param   float   $adjustPercent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
 *
 * @return  string
 */
function adjustBrightness($hexCode, $adjustPercent) {
    $hexCode = ltrim($hexCode, '#');

    if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    }

    $hexCode = array_map('hexdec', str_split($hexCode, 2));

    foreach ($hexCode as & $color) {
        $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
        $adjustAmount = ceil($adjustableLimit * $adjustPercent);

        $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }

    return '#' . implode($hexCode);
}

/*function sections_save_tax($term_id, $tt_id, $taxonomy) {
   $term = get_term($term_id, $taxonomy);
   $term_slug = $term->slug;

	// Seulement pour les Sections
	if ($taxonomy != 'section'){
    	return;
	}

	// Recuperer l'url de l'image
	// full: $post->guid 	-or-   scaled: wp_get_attachment_image_url([id], "full" ); 
	$uploads 	= wp_upload_dir();
	$imageurl 	= get_term_meta($term_id,'wpcf-s-image', true);
	$image 		= get_attachment_id($imageurl);
    //$url 		= wp_get_attachment_url( $image, 'full' );
    $url 		= wp_get_attachment_image_url( $image, 'full' );
    $path 		= scaled_image_path( $image, 'full'); // Full path
    $isScaled	= false;
    if ( $imageurl != $url && strstr($url,"-scaled") ) {
    	$isScaled = true;
     	$url = $imageurl;
     	$path = 	str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
    }

	// Recuperer la couleur de l'image (HEXA)
	$color 		= get_term_meta($term_id,'wpcf-s-color', true);
	//error_log( print_r( $color, true ) );
	if ( $color == '' ) $color = '#5d5d5d';
	$rgbacolors 	= array(
					hex2rgba(adjustBrightness($color, -0.85), 1),	// Foncé Darken 40%
					hex2rgba($color, 1),	// Clair
				);

	error_log( 'SAVE' );
	error_log( print_r( $term_id, true ) );
	error_log( print_r( $tt_id, true ) );
	error_log( print_r( $taxonomy, true ) );
	error_log( print_r( $term_slug, true ) );
	error_log( print_r( $uploads, true ) );
	error_log( print_r( $imageurl, true ) );
	error_log( print( $isScaled) );
	error_log( print_r( $image, true ) );
	error_log( print_r( $url, true ) );
	error_log( print_r( $path, true ) );
	error_log( print_r( $rgbacolors, true ) );
}*/


function sections_saved_tax($term_id, $tt_id, $taxonomy, $update) {
   $term = get_term($term_id, $taxonomy);
   $term_slug = $term->slug;

	// Seulement pour les Sections
	if ($taxonomy != 'section'){
    	return;
	}

	// Recuperer l'url de l'image
	// full: $post->guid 	-or-   scaled: wp_get_attachment_image_url([id], "full" );
	$uploads 	= wp_upload_dir();
	$imageurl 	= get_term_meta($term_id,'wpcf-s-image', true);
	$image 		= get_attachment_id($imageurl);
    //$url 		= wp_get_attachment_url( $image, 'full' );
    $url 		= wp_get_attachment_image_url( $image, 'full' );
    $path 		= scaled_image_path( $image, 'full'); // Full path
    $isScaled	= false;
    if ( $imageurl != $url && strstr($url,"-scaled") ) {
    	$isScaled = true;
     	$url = $imageurl;
     	$path = 	str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
    }

	// Recuperer la couleur de l'image (HEXA)
	$color 		= get_term_meta($term_id,'wpcf-s-color', true);
	//error_log( print_r( $color, true ) );
	if ( $color == '' ) $color = '#5d5d5d';
	$rgbacolors 	= array(
					hex2rgba(adjustBrightness($color, -0.85), 1),	// Foncé Darken 40%
					hex2rgba($color, 1),	// Clair
				);

	/*error_log( 'SAVED' );
	error_log( print_r( $term_id, true ) );
	error_log( print_r( $tt_id, true ) );
	error_log( print_r( $taxonomy, true ) );
	error_log( print_r( $term_slug, true ) );
	error_log( print_r( $uploads, true ) );
	error_log( print_r( $imageurl, true ) );
	error_log( print( $isScaled) );
	error_log( print_r( $image, true ) );
	error_log( print_r( $url, true ) );
	error_log( print_r( $path, true ) );
	error_log( print_r( $rgbacolors, true ) );*/

	// On double check si le path est bien une image
	if ( is_file($path) ) {

		// On lance le script de Justin pour générer les images
		$sc = new sections_colorize();
		$data = $sc->apply_colorize($path, $url, $rgbacolors);
		
		//print_r($data);
		//error_log( print_r( $data, true ) );

		// Si la le script retourne un array alors on stocks les URLS des nouvelles images dans des METAs fields
		if ($data) {
			$print = 	update_term_meta( $term_id, 'wpcf-s-image_colorized_PRINT', 		$data['print']['colorized']['url'] );
			$desktop = 	update_term_meta( $term_id, 'wpcf-s-image_colorized_URL', 			$data['desktop']['colorized-scaled']['url'] );
			$mobile = 	update_term_meta( $term_id, 'wpcf-s-image_colorized_mobile_URL', 	$data['mobile']['colorized-scaled']['url'] );

			if ( $print === true && $desktop === true && $mobile === true ) {
				// Then show notice
				// TODO 
			}
			
		
		}

	} else {
		error_log( 'COLORIZE : Le path n\'est pas une image' );
	}

}


//add_action( 'edit_term', 'sections_save_tax', 10, 3 ); // Arrive trop tot
add_action( 'saved_term', 'sections_saved_tax', 10, 4); // OK ? 

?>