<?php 

include_once( 'partners-colorize.php');
//include_once( 'partners-shortcode.php' );

// Get image 
function scaled_image_path($attachment_id, $size = 'thumbnail') {
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
}

function partners_save_post($post_id, $post, $update)  {
	
	// Sinon la fonction se lance dès le clic sur "ajouter"
	if(!$update) {
		return;
	}

	// On ne veut pas executer le code lorsque c'est une révision
	if(wp_is_post_revision($post_id)) {
		return;
	}

	// On évite les sauvegardes automatiques
	if ( defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE ) {
 	  	return;
	}

	// Seulement pour les Partners 
	if ($post->post_type != 'partenaire'){
    	return;
	}

	// Recuperer l'url de l'image
    $image     	= get_post_thumbnail_id();
    $url 		= wp_get_attachment_url( $image, 'full' );
    $path 		= scaled_image_path( $image, 'full'); // Full path

	error_log( print_r( $image, true ) );
	error_log( print_r( $url, true ) );
	error_log( print_r( $path, true ) );
	
	// On double check si le path est bien une image
	if ( is_file($path) ) {
		
		// On lance le script de Justin pour générer les images 
		$pc = new partners_colorize();
		$data = $pc->apply_colorize($path, $url);
		error_log( print_r( $data, true ) );
		
		// Si la le script retourne un array alors on stocks les URLS des nouvelles images dans des METAs fields 
		if ($data) {

			//update_post_meta( $post_id, '_colorized_mobile_URL', $data['mobile']['colorized']['url'] );
			$desktop = update_post_meta( $post_id, '_colorized_desktop_URL', $data['desktop']['colorized']['url'] );
			$retina = update_post_meta( $post_id, '_colorized_retina_URL', $data['retina']['colorized']['url'] );

			if ( $desktop === true && $retina === true ) {
				// Then show notice
				// TODO 
			}
			
		}

	} else {
		error_log( 'COLORIZE : Le path n\'est pas une image' );
	}

}
add_action('save_post', 'partners_save_post', 10, 3);

?>