<?php
/**
 * Template page WordPress standard
 */
get_header();
?>
<main id="swv-main">
    <div class="swv-single-wrap">
        <?php while ( have_posts() ) : the_post(); ?>
            <article class="swv-content">
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    </div>
</main>
<?php get_footer(); ?>
