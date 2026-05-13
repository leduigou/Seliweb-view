<?php
/**
 * Template pour les pages de catégories WordPress
 */
get_header();
?>
<main id="swv-main">
    <div class="swv-category-wrap">
        <h1><?php single_cat_title(); ?></h1>
        <?php if ( have_posts() ) : ?>
            <div class="swv-posts-list">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="swv-post-item">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="swv-post-meta">
                            <?php echo get_the_date(); ?> par <?php the_author(); ?>
                        </div>
                        <div class="swv-post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p>Aucun article dans cette catégorie.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>