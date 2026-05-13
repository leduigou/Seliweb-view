<footer id="swv-footer">
    <div class="swv-footer-inner">

        <div class="swv-footer-menus">
            <?php
            $footer_menus = array(
                'footer-1' => __( 'Informations', 'seliweb-view' ),
                'footer-2' => __( 'Le SEL', 'seliweb-view' ),
                'footer-3' => __( 'Contact', 'seliweb-view' ),
            );
            foreach ( $footer_menus as $location => $default_title ) :
                if ( has_nav_menu( $location ) ) : ?>
                    <div class="swv-footer-menu">
                        <h4><?php echo esc_html( $default_title ); ?></h4>
                        <?php wp_nav_menu( array(
                            'theme_location' => $location,
                            'container'      => false,
                            'depth'          => 1,
                        ) ); ?>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>

        <div class="swv-footer-bottom">
            <span>
                &copy; <?php echo date('Y'); ?>
                <a href="<?php echo esc_url( home_url('/') ); ?>"
                   style="color:rgba(255,255,255,.6);"><?php bloginfo('name'); ?></a>
            </span>
            <span><?php esc_html_e( 'Propulsé par Seliweb', 'seliweb-view' ); ?></span>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
