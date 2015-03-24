<?php

add_theme_support( 'automatic-feed-links' );
add_filter( 'the_content', 'filter_p_images' );
add_filter( 'the_content', 'swp_modify_images' );
add_filter( 'jpeg_quality', create_function( '', 'return 100;' ) );
add_action( 'after_setup_theme', 'swp_theme_setup' );
add_filter( 'image_size_names_choose', 'my_custom_sizes' );
add_filter( 'wp_generate_attachment_metadata', 'swp_wp_generate_attachment_metadata' );

function filter_p_images($content){
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '<div class="box-flex-image">\1\2\3</div>', $content);
}

function swp_modify_images($content){
    $document = new DOMDocument();
    $document->LoadHTML($content);
    $images = $document->getElementsByTagName('img');
    foreach ($images as $image){
        $filename = $image->getAttribute('src'); // legacy
        $url = $filename;
        $attachment_id = fjarrett_get_attachment_id_by_url($url);
        $wp_metadata = wp_get_attachment_metadata($attachment_id);

        $url_relative = preg_replace("(^https?:)", "", $url); // from http://stackoverflow.com/questions/4357668/how-do-i-remove-http-https-and-slash-from-user-input-in-php
        $image->setAttribute('src', $url_relative);

        // add srcset if not 'size-full' image
        if(strpos($image->getAttribute('class'),'size-full') === false){
            $extension_pos = strrpos($url_relative, '.');
            $src2x = substr($url_relative, 0, $extension_pos) . swp_retina_extension() . substr($url_relative, $extension_pos);
            $srcset = $url_relative . ' 1x, ' . $src2x . ' 2x';
            $image->setAttribute('srcset', $srcset);
        }

        // if(!is_feed()) {
            // insert lowres-images for use with https://github.com/aFarkas/lazysizes
            // $image->setAttribute('data-src', $filename);
            // $image->setAttribute('class', $image->getAttribute('class') . ' lazyload');
            // $extension_pos = strrpos($filename, '.');
            // $new_src = substr($filename, 0, $extension_pos) . '-lowres' . substr($filename, $extension_pos);
            // $image->setAttribute('src', $new_src);
        // }
    }
    // from http://php.net/manual/de/domdocument.savehtml.php
    return preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $document->saveHTML()));
    return $content;
}

function swp_theme_setup(){
    add_image_size( 'extra-large', 1920, 1920);
}

function my_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'extra-large' => __( 'Extra Large' ),
    ) );
}

function swp_wp_generate_attachment_metadata($metadata){
    if ( swp_is_image_meta( $metadata ) )
        swp_generate_images( $metadata);
    return $metadata;
}

function swp_is_image_meta($metadata){
    if ( !isset( $metadata ) )
        return false;
    if ( !isset( $metadata['sizes'] ) )
        return false;
    if ( !isset( $metadata['width'], $metadata['height'] ) ) {
        // wr2x_log( "[WARN] No width and height in the metadata for #" . $id . "." );
        return false;
    }
    return true;
}

function swp_retina_extension($dot = false){
    $retina_extension = '@2x';
    if($dot){
        $retina_extension .= '.';
    }
    return $retina_extension;
}

function swp_lowres_extension($dot = false){
    $lowres_extension = '-lowres';
    if($dot){
        $lowres_extension .= '.';
    }
    return $lowres_extension;
}

function wr2x_get_image_sizes() {
    $sizes = array();
    global $_wp_additional_image_sizes;
    foreach (get_intermediate_image_sizes() as $s) {
        $crop = false;
        if (isset($_wp_additional_image_sizes[$s])) {
            $width = intval($_wp_additional_image_sizes[$s]['width']);
            $height = intval($_wp_additional_image_sizes[$s]['height']);
            $crop = $_wp_additional_image_sizes[$s]['crop'];
        } else {
            $width = get_option( $s . '_size_w' );
            $height = get_option( $s . '_size_h' );
            $crop = get_option( $s . '_crop' );
        }
        $sizes[$s] = array( 'width' => $width, 'height' => $height, 'crop' => $crop );
    }
    return $sizes;
}

function swp_are_dimensions_ok( $width, $height, $retina_width, $retina_height ) {
    $w_margin = $width - $retina_width;
    $h_margin = $height - $retina_height;
    return ( $w_margin >= -2 && $h_margin >= -2 );
}

// Based on http://wordpress.stackexchange.com/questions/6645/turn-a-url-into-an-attachment-post-id
function swp_get_attachment_id($file){
    $query = array(
        'post_type' => 'attachment',
        'meta_query' => array(
            array(
                'key'		=> '_wp_attached_file',
                'value'		=> ltrim( $file, '/' )
            )
        )
    );
    $posts = get_posts( $query );
    foreach( $posts as $post )
        return $post->ID;
    return false;
}

function swp_generate_images($metadata){
    $meta = $metadata;
    require('wr2x_vt_resize.php');
    global $_wp_additional_image_sizes;
    $sizes = wr2x_get_image_sizes();
    if ( !isset( $meta['file'] ) )
        return;
    $originalfile = $meta['file'];
    $uploads = wp_upload_dir();
    $pathinfo = pathinfo( $originalfile );
    $original_basename = $pathinfo['basename'];
    $basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];
    $ignore = []; // wr2x_getoption( "ignore_sizes", "wr2x_basics", array() );
    $issue = false;
    $id = swp_get_attachment_id( $meta['file'] );;

    // wr2x_log("* GENERATE RETINA FOR ATTACHMENT '{$meta['file']}'");
    // wr2x_log( "Full Size is {$original_basename}." );

    foreach ( $sizes as $name => $attr ) {
        $normal_file = "";
        if ( in_array( $name, $ignore ) ) {
            // wr2x_log( "Retina for {$name} ignored (settings)." );
            continue;
        }
        // Is the file related to this size there?
        $pathinfo = null;
        $retina_file = null;
        $lowres_file = null;

        if ( isset( $meta['sizes'][$name] ) && isset( $meta['sizes'][$name]['file'] ) ) {
            $normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
            $pathinfo = pathinfo( $normal_file ) ;
            $retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . swp_retina_extension(true) . $pathinfo['extension'];
            $lowres_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . swp_lowres_extension(true) . $pathinfo['extension'];
        }

        if ( $retina_file && file_exists( $retina_file ) ) {
            // wr2x_log( "Base for {$name} is '{$normal_file }'." );
            // wr2x_log( "Retina for {$name} already exists: '$retina_file'." );
            continue;
        }
        if ( $retina_file ) {
            $originalfile = trailingslashit( $pathinfo['dirname'] ) . $original_basename;

            if ( !file_exists( $originalfile ) ) {
                // wr2x_log( "[ERROR] Original file '{$originalfile}' cannot be found." );
                return $meta;
            }

            // Maybe that new image is exactly the size of the original image.
            // In that case, let's make a copy of it.
            if ( $meta['sizes'][$name]['width'] * 2 == $meta['width'] && $meta['sizes'][$name]['height'] * 2 == $meta['height'] ) {
                copy ( $originalfile, $retina_file );
                // wr2x_log( "Retina for {$name} created: '{$retina_file}' (as a copy of the full-size)." );
            }
            // Otherwise let's resize (if the original size is big enough).
            else if ( swp_are_dimensions_ok( $meta['width'], $meta['height'], $meta['sizes'][$name]['width'] * 2, $meta['sizes'][$name]['height'] * 2 ) ) {
                // Change proposed by Nicscott01, slighlty modified by Jordy (+isset)
                // (https://wordpress.org/support/topic/issue-with-crop-position?replies=4#post-6200271)
                $crop = isset( $_wp_additional_image_sizes[$name] ) ? $_wp_additional_image_sizes[$name]['crop'] : true;
                $customCrop = null;

                // Support for Manual Image Crop
                // If the size of the image was manually cropped, let's keep it.
                if ( class_exists( 'ManualImageCrop' ) && isset( $meta['micSelectedArea'] ) && isset( $meta['micSelectedArea'][$name] ) && isset( $meta['micSelectedArea'][$name]['scale'] ) ) {
                    $customCrop = $meta['micSelectedArea'][$name];
                }
                $image = wr2x_vt_resize( $originalfile, $meta['sizes'][$name]['width'] * 2,
                    $meta['sizes'][$name]['height'] * 2, $crop, $retina_file, $customCrop );
            }
            if ( !file_exists( $retina_file ) ) {
                // wr2x_log( "[ERROR] Retina for {$name} could not be created. Full Size is " . $meta['width'] . "x" . $meta['height'] . " but Retina requires a file of at least " . $meta['sizes'][$name]['width'] * 2 . "x" . $meta['sizes'][$name]['height'] * 2 . "." );
                $issue = true;
            }
            else {
                do_action( 'swp_retina_file_added', $id, $retina_file );
                // wr2x_log( "Retina for {$name} created: '{$retina_file}'." );
            }
        } else {
/*            if ( empty( $normal_file ) )
                wr2x_log( "[ERROR] Base file for '{$name}' does not exist." );
            else
                wr2x_log( "[ERROR] Base file for '{$name}' cannot be found here: '{$normal_file}'." );*/
        }

        if ( $lowres_file ) {
            $originalfile = trailingslashit( $pathinfo['dirname'] ) . $original_basename;
            $crop = isset( $_wp_additional_image_sizes[$name] ) ? $_wp_additional_image_sizes[$name]['crop'] : true;
            $image = wr2x_vt_resize( $originalfile, $meta['sizes'][$name]['width'], $meta['sizes'][$name]['height'], $crop, $lowres_file, false, 10);
        }
    }

    // Checks attachment ID + issues
/*    if ( !$id )
        return $meta;
    if ( $issue )
        wr2x_add_issue( $id );
    else
        wr2x_remove_issue( $id );*/
    return $meta;
}

// from http://frankiejarrett.com/get-an-attachment-id-by-url-in-wordpress/
/**
 * Return an ID of an attachment by searching the database with the file URL.
 *
 * First checks to see if the $url is pointing to a file that exists in
 * the wp-content directory. If so, then we search the database for a
 * partial match consisting of the remaining path AFTER the wp-content
 * directory. Finally, if a match is found the attachment ID will be
 * returned.
 *
 * @param string $url The URL of the image (ex: http://mysite.com/wp-content/uploads/2013/05/test-image.jpg)
 *
 * @return int|null $attachment Returns an attachment ID, or null if no attachment is found
 */
function fjarrett_get_attachment_id_by_url( $url ) {
    // Split the $url into two parts with the wp-content directory as the separator
    $parsed_url  = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );

    // Get the host of the current site and the host of the $url, ignoring www
    $this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
    $file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );

    // Return nothing if there aren't any $url parts or if the current host and $url host do not match
    if ( ! isset( $parsed_url[1] ) || empty( $parsed_url[1] ) || ( $this_host != $file_host ) ) {
        return;
    }

    // Now we're going to quickly search the DB for any attachment GUID with a partial path match
    // Example: /uploads/2013/05/test-image.jpg
    $parsed_url[1] = preg_replace( '/-[0-9]{1,4}x[0-9]{1,4}\.(jpg|jpeg|png|gif|bmp)$/i', '.$1', $parsed_url[1] );
    global $wpdb;

    $attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}posts WHERE guid RLIKE %s;", $parsed_url[1] ) );

    // Returns null if no attachment is found
    return $attachment[0];
}