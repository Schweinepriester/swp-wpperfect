<?php
function filter_p_images($content){
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '<div class="box-flex-image">\1\2\3</div>', $content);
}

function remove_width_height($html){
    $html =  preg_replace('/width="(\d+)"\s*height="(\d+)"/', '', $html);
    return $html;
}

add_filter('the_content', 'filter_p_images');
// add_filter('image_send_to_editor', 'remove_width_height');