<?php

add_theme_support( 'automatic-feed-links' ); // activate wordpress feeds
// add_filter( 'wp_get_attachment_url', 'swp_make_link_protocol_relative' ); // make all attachments urls protocol relative

add_filter( 'the_content', 'filter_p_images' );
// add_filter( 'the_content', 'swp_modify_images' );
// add_filter( 'jpeg_quality', create_function( '', 'return 100;' ) );
add_action( 'after_setup_theme', 'swp_theme_setup' );
add_filter( 'image_size_names_choose', 'my_custom_sizes' );

function swp_make_link_protocol_relative($link){
    return preg_replace("(^https?:)", "", $link); // from http://stackoverflow.com/questions/4357668/how-do-i-remove-http-https-and-slash-from-user-input-in-php
}

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
        // $attachment_id = fjarrett_get_attachment_id_by_url($url);
        // $wp_metadata = wp_get_attachment_metadata($attachment_id);

        $url_relative = swp_make_link_protocol_relative($url);
        // $url_relative = $url;
        $image->setAttribute('src', $url_relative);

        // add srcset if retina version exists
        $img_pathinfo = wr2x_get_pathinfo_from_image_src($url);
        $filepath = trailingslashit( ABSPATH ) . $img_pathinfo;
        $potential_retina = wr2x_get_retina( $filepath );
        if ( $potential_retina != null ) {
            $retina_url = wr2x_from_system_to_url($potential_retina);
            $retina_url = swp_make_link_protocol_relative($retina_url);
            $srcset = $url_relative . ' 1x, ' . $retina_url . ' 2x';
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
}

function swp_theme_setup(){
    add_image_size( 'extra-large', 1920, 1920);
}

function my_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'extra-large' => __( 'Extra Large' ),
    ) );
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
