<?php get_header(); ?>

<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php
                    the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
                ?>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <footer class="entry-footer">
                
            </footer>
        </article>
    <?php
    endwhile;
else :
    // TODO
endif;
?>

<?php get_footer(); ?>