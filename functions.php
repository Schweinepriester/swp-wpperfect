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
}

function my_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'extra-large' => __( 'Extra Large' ),
    ) );
}

add_filter('the_content', 'filter_p_images');
// add_filter('image_send_to_editor', 'remove_width_height');
add_filter( 'jpeg_quality', create_function( '', 'return 100;' ) );
add_action( 'after_setup_theme', 'swp_theme_setup' );
add_filter( 'image_size_names_choose', 'my_custom_sizes' );