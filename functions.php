<?php
// activate wordpress feeds
add_theme_support( 'automatic-feed-links' );

// let wordpress handle <title>
add_action( 'after_setup_theme', 'theme_slug_setup' );

// adjust DOM to my taste
add_filter( 'the_content', 'swp_content' );

add_action( 'after_setup_theme', 'swp_theme_setup' );
add_action( 'send_headers', 'swp_security_header' );

// from http://antsanchez.com/remove-new-wordpress-emoji-support/
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// https://ewww.io/2016/03/30/ewww-image-optimizer-actions-hooks/
add_action( 'ewww_image_optimizer_post_optimization', 'swp_remove_metadata', 10, 2 );

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

    // Referrer-Policy - from <https://scotthelme.co.uk/a-new-security-header-referrer-policy/>
    header('Referrer-Policy: same-origin');
}

function swp_content($content)
{
    if (strlen($content) == 0) { // empty string?
        return $content; // finish
    }

    // mostly from http://stackoverflow.com/questions/29303143/wrap-img-elements-in-div-but-allow-for-a-tags
    $doc = new DOMDocument();
    libxml_use_internal_errors(true); // first found as solution for invalid element <mark>, which is valid HTML5 #phpfail
    // https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
    $doc->loadHtml('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($doc);
    $lists = $xpath->query(
        '//*[self::ul or self::ol]' // all <ul> and <ol>
    );

    $divList = $doc->createElement('div');
    foreach ($lists as $list) {
        $div = $divList->cloneNode();
        $list->parentNode->insertBefore($div, $list);
        $div->appendChild($list);
    }

    $imagePs = $xpath->query(
        '//*[self::p/child::img or self::p/child::a/child::img]'
    );
    
    $divImages = $doc->createElement('figure');
    $divImages->setAttribute('class', 'box-flex-image');
    foreach ($imagePs as $imageP) {
        $div = $divImages->cloneNode();
        $div->appendChild($imageP->firstChild);
        $imageP->parentNode->replaceChild($div, $imageP);
    }

    return substr($doc->saveHTML($xpath->query('//*[self::body]')->item(0)), strlen('<body>'), -strlen('</body>'));
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

function swp_remove_metadata( $file, $type ) {
    // its not necessary to check for original image or not, as the original doesnt get optimized! … or at least called here
    // TODO or does it!?
    error_log($type);
    if ($type === 'image/jpeg') {
        $image = new Imagick($file);

        // set 4:2:0 per https://stackoverflow.com/a/27147203
        $image->setSamplingFactors(array('2x2', '1x1', '1x1'));

        // strip every profile like EXIF etc. except ICC for color profile
        foreach ( $image->getImageProfiles( '*', true ) as $key => $value ) {
            if ( $key !== 'icc' ) {
                $image->removeImageProfile( $key );
            }
        }

        $image->setImageCompressionQuality(85); // TODO set quality = wordpress

        $image->setInterlaceScheme(Imagick::INTERLACE_PLANE); // progressive

        $image->writeImage();
    }
//    remove_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 ); // remove EWWWIO_Imagick_Editor temporarily
//    $image = wp_get_image_editor( $file );
//    error_log(get_class($image));
//    if ( is_wp_error( $image ) ) {
//        error_log("fail");
//    }
//    $image->strip_meta(); // only imagick knows this // TODO this is protected in the class, i hacked it to be public…
//    $image->save();
//    add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 ); // add EWWWIO_Imagick_Editor back
}
