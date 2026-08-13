<?php
/**
 * Template principal — fallback générique WordPress
 * (articles de blog, recherche, etc. ; les annonces sont gérées par le
 * modèle de page "Annonces SEL" fourni par le plugin Seliweb)
 */
get_header();
?>
<main id="swv-main">
    <div class="swv-single-wrap">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <article <?php post_class( 'swv-post-item' ); ?>>
                    <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                    <div class="swv-post-meta">
                        <?php echo get_the_date(); ?> par <?php the_author(); ?>
                    </div>
                    <div class="swv-post-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Aucun contenu trouvé.', 'seliweb-view' ); ?></p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
