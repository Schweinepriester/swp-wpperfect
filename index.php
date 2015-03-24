<?php get_header(); ?>
<main>
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
                    <div>
                        <?php echo get_the_date(); ?>, <?php the_time(); ?> von <?php the_author(); ?>
                    </div>
                </footer>
            </article>
        <?php
        endwhile;
    else :
        // TODO
    endif;
    ?>
</main>
<?php get_footer(); ?>