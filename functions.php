<?php
function filter_images($content){
    // '<div class="box-flex-image">\1\2\3</div>'
    return preg_replace_callback('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', function($match){
        if(strpos($match[0],'large-image') !== false){
            return 'BLA';
        } else {
            return '<div class="box-flex-image">'.$match[0].'</div>';
        }
    }, $content);
}

add_filter('the_content', 'filter_images');