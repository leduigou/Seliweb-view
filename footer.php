<footer id="swv-footer">
    <div class="swv-footer-inner">

        <div class="swv-footer-menus swv-footer-menus--<?php echo esc_attr( swv_footer_align() ); ?>">
            <?php
            $swv_col = 0;
            foreach ( swv_footer_menus() as $location => $conf ) :
                $swv_col++;
                if ( ! has_nav_menu( $location ) ) continue; ?>
                <div class="swv-footer-menu">
                    <div class="swv-footer-menu-title swv-footer-menu-title-<?php echo (int) $swv_col; ?>">
                        <?php echo swv_footer_menu_title_html( $conf['mod'] ); // <h4> déjà échappé ?>
                    </div>
                    <?php wp_nav_menu( array(
                        'theme_location' => $location,
                        'container'      => false,
                        'depth'          => 1,
                    ) ); ?>
                </div>
            <?php endforeach; ?>
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
