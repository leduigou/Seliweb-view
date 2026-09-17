<footer id="swv-footer">
    <div class="swv-footer-inner">

        <?php
        $swv_footer_actives = array_filter( array( 1, 2, 3 ), function ( $i ) {
            return is_active_sidebar( 'swv-footer-' . $i );
        } );
        if ( $swv_footer_actives ) : ?>
            <div class="swv-footer-widgets">
                <?php foreach ( $swv_footer_actives as $i ) : ?>
                    <div class="swv-footer-widget-col">
                        <?php dynamic_sidebar( 'swv-footer-' . $i ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
