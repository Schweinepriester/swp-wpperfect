<?php

// activate wordpress feeds
add_theme_support( 'automatic-feed-links' );

// let wordpress handle <title>
add_action( 'after_setup_theme', 'theme_slug_setup' );

// use the modified image editor
add_filter( 'wp_image_editors', 'swp_image_editors');

// hook the function to the upload handler
add_action('media_handle_upload', 'swp_uploadprogressive');

add_filter( 'the_content', 'filter_p_images' );

add_action( 'after_setup_theme', 'swp_theme_setup' );
add_action( 'send_headers', 'swp_security_header' );
add_action( 'send_headers', 'swp_hpkp' );

// from http://antsanchez.com/remove-new-wordpress-emoji-support/
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// from https://make.wordpress.org/core/2014/10/29/title-tags-in-4-1/
function theme_slug_setup() {
    add_theme_support( 'title-tag' );
}

function swp_security_header() {
    // HSTS - from <https://thomasgriffin.io/enable-http-strict-transport-security-hsts-wordpress/>
    header( 'Strict-Transport-Security: max-age=15768000; includeSubDomains' );

    // X-Frame-Options - from <https://scotthelme.co.uk/hardening-your-http-response-headers/#x-frame-options>
    header( 'X-Frame-Options: SAMEORIGIN' );

    // X-Xss-Protection - from <https://scotthelme.co.uk/hardening-your-http-response-headers/#x-xss-protection>
    header( 'X-Xss-Protection: 1; mode=block' );

    // X-Content-Type-Options - from <https://scotthelme.co.uk/hardening-your-http-response-headers/#x-content-type-options>
    header( 'X-Content-Type-Options: nosniff' );
}

function swp_hpkp(){
    // include get_home_path().'./../swp_wp_extra/swp_hpkp.php';
}

function filter_p_images($content){
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '<div class="box-flex-image">\1\2\3</div>', $content);
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
