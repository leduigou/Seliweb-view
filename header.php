<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$header_image = get_header_image();
$has_logo     = has_custom_logo();
$logo         = get_custom_logo();

// URL de la page login front-end (page contenant [seliweb_login])
global $wpdb;
$login_page_id = $wpdb->get_var(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_status='publish' AND post_type='page'
       AND post_content LIKE '%seliweb_login%' LIMIT 1"
);
$compte_page_id = $wpdb->get_var(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_status='publish' AND post_type='page'
       AND post_content LIKE '%seliweb_mon_compte%' LIMIT 1"
);
$inscription_page = get_page_by_path( 'inscription-sel' );
$inscription_url  = $inscription_page ? get_permalink( $inscription_page->ID ) : wp_registration_url();

if ( is_user_logged_in() ) {
    // Connecté : lien vers Mon compte + bouton déconnexion
    $compte_url   = $compte_page_id ? get_permalink($compte_page_id) : home_url('/');
    // URL déconnexion avec redirect vers la page login front-end
    $logout_url   = wp_logout_url( $login_page_id ? get_permalink($login_page_id) : home_url('/') );
    $user         = wp_get_current_user();
    $login_label  = esc_html( $user->display_name );
    $btn_url      = $compte_url;
    $logout_shown = true;
} else {
    // Non connecté : lien vers la page login front-end (ou wp-login en fallback)
    $btn_url      = $login_page_id ? get_permalink($login_page_id) : wp_login_url( home_url('/') );
    $login_label  = __( 'Connexion', 'seliweb-view' );
    $logout_shown = false;
}
?>

<header id="swv-header"
    <?php if ( $header_image ) :
        echo 'class="has-banner" style="background-image:url(' . esc_url($header_image) . ');"';
    endif; ?>>
    <div class="swv-header-inner">

        <!-- Logo + titre -->
        <div class="swv-site-brand">
            <?php if ( $has_logo ) : ?>
                <div class="swv-site-logo"><?php echo $logo; ?></div>
            <?php else : ?>
                <div class="swv-site-logo-placeholder">&#9752;</div>
            <?php endif; ?>
            <div>
                <div class="swv-site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>" style="color:inherit;text-decoration:none;">
                        <?php bloginfo('name'); ?>
                    </a>
                </div>
                <?php $desc = get_bloginfo('description'); if ($desc) : ?>
                    <div class="swv-site-desc"><?php echo esc_html($desc); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bouton compte / connexion -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-shrink:0;">
            <a href="<?php echo esc_url($btn_url); ?>" class="swv-login-btn">
                <svg class="swv-login-icon" viewBox="0 0 36 36" fill="none"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="18" cy="13" r="7" fill="rgba(255,255,255,.9)"/>
                    <path d="M4 34c0-7.732 6.268-14 14-14s14 6.268 14 14"
                          stroke="rgba(255,255,255,.9)" stroke-width="2.5"
                          stroke-linecap="round" fill="none"/>
                </svg>
                <span class="swv-login-label"><?php echo esc_html($login_label); ?></span>
            </a>

            <?php if ($logout_shown) : ?>
                <a href="<?php echo esc_url($logout_url); ?>"
                   style="font-size:.72rem;color:rgba(255,255,255,.65);text-decoration:none;"
                   onmouseover="this.style.color='#fff'"
                   onmouseout="this.style.color='rgba(255,255,255,.65)'">
                    <?php esc_html_e('Déconnexion','seliweb-view'); ?>
                </a>
            <?php elseif ( get_option('users_can_register') ) : ?>
                <a href="<?php echo esc_url( $inscription_url ); ?>"
                   style="font-size:.72rem;color:rgba(255,255,255,.65);text-decoration:none;"
                   onmouseover="this.style.color='#fff'"
                   onmouseout="this.style.color='rgba(255,255,255,.65)'">
                    <?php esc_html_e("S'inscrire",'seliweb-view'); ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<!-- Navigation -->
<?php if ( has_nav_menu('primary') ) : ?>
<nav id="swv-nav" aria-label="<?php esc_attr_e('Navigation principale','seliweb-view'); ?>">
    <div class="swv-nav-inner">
        <?php wp_nav_menu(array('theme_location'=>'primary','container'=>false,'depth'=>2)); ?>
    </div>
</nav>
<?php endif; ?>
