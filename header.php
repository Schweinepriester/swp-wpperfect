<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <title><?php wp_title(); ?></title>
        <!-- <link rel="profile" href="http://gmpg.org/xfn/11" /> !? -->
        <link rel="stylesheet" href="<?php echo get_template_directory_uri (); ?>/css/normalize.css" type="text/css" media="all" />
        <link rel="stylesheet" href="https://code.cdn.mozilla.net/fonts/fira.css" type='text/css'>
        <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>" type="text/css" media="screen" />
        <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' ); ?>
        <?php wp_head(); ?>
        <script src="<?php echo get_template_directory_uri (); ?>/js/iconic.min.js"></script>
    </head>

    <body <?php body_class(); ?>>
        <header class="side-header">
            <h1>
                <a href="<?php bloginfo('url'); ?>/"><?php bloginfo('name'); ?></a>
            </h1>
            <nav>
                <ul>
                    <?php wp_list_pages('title_li='); ?>
                </ul>
            </nav>
        </header>
