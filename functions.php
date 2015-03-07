<?php
function filter_p_images($content){
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '<div class="box-flex-image">\1\2\3</div>', $content);
}

function remove_width_height($html){
    $html =  preg_replace('/width="(\d+)"\s*height="(\d+)"/', '', $html);
    return $html;
}

function swp_theme_setup(){
    add_image_size( 'extra-large', 1920, 1920);
    // add_image_size( 'extra-large-lowres', 1920, 1920);
}

function my_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'extra-large' => __( 'Extra Large' ),
    ) );
}

function ad_update_jpeg_quality($meta_id, $attach_id, $meta_key, $attach_meta) {

    if ($meta_key == '_wp_attachment_metadata') {

        $post = get_post($attach_id);

        if ($post->post_mime_type == 'image/jpeg' && is_array($attach_meta['sizes'])) {

            $pathinfo = pathinfo($attach_meta['file']);
            $uploads = wp_upload_dir();
            $dir = $uploads['basedir'] . '/' . $pathinfo['dirname'];

            foreach ($attach_meta['sizes'] as $size => $value) {

                $image = $dir . '/' . $value['file'];
                $resource = imagecreatefromjpeg($image);

                if ($size == 'extra-large-lowres') {
                    // set the jpeg quality for 'spalsh' size
                    imagejpeg($resource, $image, 10);
                } elseif ($size == 'spalsh1') {
                    // set the jpeg quality for the 'splash1' size
                    // imagejpeg($resource, $image, 30);
                } else {
                    // set the jpeg quality for the rest of sizes
                    // imagejpeg($resource, $image, 100);
                    continue;
                }

                // or you can skip a paticular image size
                // and set the quality for the rest:
                // if ($size == 'splash') continue;

                imagedestroy($resource);
            }
        }
    }
}

add_filter('the_content', 'filter_p_images');
// add_filter('image_send_to_editor', 'remove_width_height');
add_filter( 'jpeg_quality', create_function( '', 'return 100;' ) );
add_action( 'after_setup_theme', 'swp_theme_setup' );
add_filter( 'image_size_names_choose', 'my_custom_sizes' );
// add_action('added_post_meta', 'ad_update_jpeg_quality');