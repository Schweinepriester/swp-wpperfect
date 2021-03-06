<?php get_header(); ?>
<main>
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h2 class="entry-title">
                        <a href="<?php esc_url(the_permalink()); ?>" rel="bookmark">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                </header>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
                <footer class="entry-footer">
                    <?php
                    if ( !is_page() ) :
                        if ( has_tag() ) :
                            ?>
                            <p>
                                <?php the_tags(''); ?>
                            </p>
                            <?php
                        endif;
                        ?>
                        <p><time title="<?php the_time('c'); // TODO fix fcking time?>" datetime="<?php the_time('c');?>"><?php echo get_the_date(); ?>, <?php the_time(); ?></time> von <?php the_author(); ?></p>
                        <?php
                    endif;
                    ?>
                </footer>
            </article>
        <?php
        endwhile;
        // <p><?php posts_nav_link(); </p>
    else :
        // TODO
    endif;
    ?>
</main>
<?php get_footer(); ?>
