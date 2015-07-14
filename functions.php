<?php

add_theme_support( 'automatic-feed-links' ); // activate wordpress feeds
// add_theme_support('soil-relative-urls');

// use the modified image editor!
add_filter( 'wp_image_editors', 'swp_image_editors');

// hook the function to the upload handler
add_action('media_handle_upload', 'swp_uploadprogressive');

add_filter( 'the_content', 'filter_p_images' );
// add_filter( 'the_content', 'swp_modify_images' );
add_action( 'after_setup_theme', 'swp_theme_setup' );
add_action( 'send_headers', 'swp_strict_transport_security' );

// legacy
// add_filter( 'image_size_names_choose', 'my_custom_sizes' );

// from http://antsanchez.com/remove-new-wordpress-emoji-support/
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Enables the HTTP Strict Transport Security (HSTS) header.
 * From <https://thomasgriffin.io/enable-http-strict-transport-security-hsts-wordpress/>
 *
 * @since 1.0.0
 */
function swp_strict_transport_security() {
    header( 'Strict-Transport-Security: max-age=31536000' );
}

function filter_p_images($content){
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '<div class="box-flex-image">\1\2\3</div>', $content);
}

function swp_modify_images($content){
    $document = new DOMDocument();
    $document->loadHTML($content);
    $images = $document->getElementsByTagName('img');

    foreach ($images as $image){
        $imageHtml = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $document->saveHTML($image)));
        $imageSrc = fjarrett_get_attachment_id_by_url($image->getAttribute('src'));
        $imageSizePre = explode(' ', explode('size-', $image->getAttribute('class'))[1])[0];
        $imageSize = $imageSizePre;
        $imageNewString = tevkori_extend_image_tag($imageHtml, $imageSrc, "", "", "", "", $imageSize, "");
        error_log($imageNewString);
        $imageNew = $document->createDocumentFragment();
        $imageNew->appendXML($imageNewString);
        $image->parentNode->replaceChild($imageNew, $image);
        continue;

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

if ( ! isset( $content_width ) )
    $content_width = 1024;

function swp_theme_setup(){
    update_option( 'thumbnail_size_w', 300 );
    update_option( 'thumbnail_size_h', 300 );

    // TODO thumbnail @2x?

    // iphone 5(s) size
    $medium_width = 320;
    $medium_height = 568;
    update_option( 'medium_size_w', $medium_width );
    update_option( 'medium_size_h', $medium_height );

    add_image_size( 'medium@2x', $medium_width*2, $medium_height*2);

    update_option( 'large_size_w', 1024 );
    update_option( 'large_size_h', 1024 );

    add_image_size( 'larger', 1500, 1500);

    add_image_size( 'extra-large', 2048, 2048);
}

function swp_custom_sizes( $sizes ) {
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

function swp_image_editors($image_editors){
    require_once get_stylesheet_directory() . '/class-wp-image-editor-imagick-swp.php';
    return array('WP_Image_Editor_Imagick_Swp');
}

function swp_uploadprogressive($image_data){
    $image_editor = wp_get_image_editor($image_data['file']);
    $saved_image = $image_editor->save($image_data['file']);
    return $image_data;
}

// from http://stackoverflow.com/questions/19802157/change-wordpress-default-gallery-output
add_filter('post_gallery', 'my_post_gallery', 10, 2);
function my_post_gallery($output, $attr) {
    global $post;

    if (isset($attr['orderby'])) {
        $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
        if (!$attr['orderby'])
            unset($attr['orderby']);
    }

    extract(shortcode_atts(array(
        'order' => 'ASC',
        'orderby' => 'menu_order ID',
        'id' => $post->ID,
        'itemtag' => 'dl',
        'icontag' => 'dt',
        'captiontag' => 'dd',
        'columns' => 3,
        'size' => 'thumbnail',
        'include' => '',
        'exclude' => ''
    ), $attr));

    $id = intval($id);
    if ('RAND' == $order) $orderby = 'none';

    if (!empty($include)) {
        $include = preg_replace('/[^0-9,]+/', '', $include);
        $_attachments = get_posts(array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));

        $attachments = array();
        foreach ($_attachments as $key => $val) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    }

    if (empty($attachments)) return '';

    // Here's your actual output, you may customize it to your need
    $output = "<div class=\"slideshow-wrapper\">\n";
    // $output .= "<ul data-orbit>\n";

    // Now you loop through each attachment
    foreach ($attachments as $id => $attachment) {
        // Fetch the thumbnail (or full image, it's up to you)
//      $img = wp_get_attachment_image_src($id, 'medium');
//      $img = wp_get_attachment_image_src($id, 'my-custom-image-size');

        /*$img = wp_get_attachment_image_src($id, 'large');

        $output .= "<a>\n";
        $output .= tevkori_extend_image_tag("<img src=\"{$img[0]}\" width=\"{$img[1]}\" height=\"{$img[2]}\" alt=\"\" />\n", $id, null, null, null, null, 'large', null);
        $output .= "</a>\n";*/

        // $output .= "<a>\n";
        $output .= wp_get_attachment_link( $id, 'full');
        // $output .= "</a>\n";
    }

    // $output .= "</ul>\n";
    $output .= '<div style="clear: both"></div>';
    $output .= "</div>\n";

    return $output;
}
