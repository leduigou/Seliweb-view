<?php
/**
 * Seliweb View — functions.php
 *
 * Ce fichier contient uniquement le code de présentation du thème :
 * Customizer, CSS dynamique, setup WordPress, enqueue des assets.
 *
 * Les fonctions métier (pagination, recherche, cartes annonces, menu)
 * sont dans le plugin : includes/class-front.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ================================================================
// HELPERS : lecture des réglages du Customizer
// (avec valeurs par défaut si non configuré)
// ================================================================
function swv_opt( $key, $default = null ) {
    $defaults = array(
        'display_mode' => 'grille',
        'grid_cols'    => 3,
        'per_page'     => 12,
        'color_primary'=> '#1d6a4a',
    );
    $d = $default !== null ? $default : ( $defaults[ $key ] ?? null );
    return get_theme_mod( 'swv_' . $key, $d );
}

// Raccourcis utilisés dans les templates
function swv_display_mode() { return swv_opt('display_mode'); }
function swv_grid_cols()    { return intval( swv_opt('grid_cols') ); }
function swv_color()        { return swv_opt('color_primary'); }

// Pont vers le plugin : swv_render_card() utilise apply_filters('seliweb_display_mode', 'grille')
// ce filtre permet au thème d'imposer le mode choisi dans le Customizer.
add_filter( 'seliweb_display_mode', 'swv_display_mode' );

// ================================================================
// CUSTOMIZER
// ================================================================
function swv_customizer( $wp_customize ) {

    // ---- Section ------------------------------------------------
    $wp_customize->add_section( 'swv_settings', array(
        'title'    => __( 'Seliweb View', 'seliweb-view' ),
        'priority' => 30,
    ) );

    // ---- Mode d'affichage --------------------------------------
    $wp_customize->add_setting( 'swv_display_mode', array(
        'default'           => 'grille',
        'sanitize_callback' => function($v){ return in_array($v,array('liste','grille')) ? $v : 'grille'; },
    ) );
    $wp_customize->add_control( 'swv_display_mode', array(
        'label'   => __( 'Mode d\'affichage des annonces', 'seliweb-view' ),
        'section' => 'swv_settings',
        'type'    => 'radio',
        'choices' => array(
            'grille' => __( 'Grille (colonnes)', 'seliweb-view' ),
            'liste'  => __( 'Liste',             'seliweb-view' ),
        ),
    ) );

    // ---- Nombre de colonnes ------------------------------------
    $wp_customize->add_setting( 'swv_grid_cols', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'swv_grid_cols', array(
        'label'       => __( 'Colonnes en mode grille', 'seliweb-view' ),
        'description' => __( 'Ignoré en mode liste.', 'seliweb-view' ),
        'section'     => 'swv_settings',
        'type'        => 'select',
        'choices'     => array(
            2 => __( '2 colonnes', 'seliweb-view' ),
            3 => __( '3 colonnes', 'seliweb-view' ),
            4 => __( '4 colonnes', 'seliweb-view' ),
        ),
    ) );

    // ---- Couleur principale ------------------------------------
    $wp_customize->add_setting( 'swv_color_primary', array(
        'default'           => '#1d6a4a',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage', // mise à jour en temps réel
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'swv_color_primary',
        array(
            'label'   => __( 'Couleur principale du thème', 'seliweb-view' ),
            'section' => 'swv_settings',
        )
    ) );
}
add_action( 'customize_register', 'swv_customizer' );

// ================================================================
// CSS DYNAMIQUE — injecte la couleur choisie dans des variables CSS
// ================================================================
function swv_dynamic_css() {
    $color = swv_color();
    $dark  = swv_darken_hex( $color, 20 );
    echo '<style id="swv-dynamic-css">
    :root {
        --color-primary:    ' . esc_attr($color) . ';
        --color-primary-dk: ' . esc_attr($dark)  . ';
        --color-header-bg:  ' . esc_attr($color) . ';
        --color-footer-bg:  ' . esc_attr($dark)  . ';
    }
    </style>' . "\n";
}
add_action( 'wp_head', 'swv_dynamic_css' );

// Mise à jour temps réel dans le Customizer (postMessage)
function swv_customizer_live() { ?>
<script>
(function($){
    wp.customize('swv_color_primary', function(v){
        v.bind(function(color){
            var style = document.getElementById('swv-live-color');
            if (!style) { style = document.createElement('style'); style.id = 'swv-live-color'; document.head.appendChild(style); }
            style.textContent = ':root { --color-primary:'+color+'; --color-header-bg:'+color+'; }';
        });
    });
}(jQuery));
</script>
<?php }
add_action( 'customize_preview_init', function(){
    add_action( 'wp_footer', 'swv_customizer_live' );
} );

// ================================================================
// HELPER : assombrir une couleur hex
// ================================================================
function swv_darken_hex( $hex, $percent = 20 ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen($hex) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = max( 0, hexdec(substr($hex,0,2)) - round(255*$percent/100) );
    $g = max( 0, hexdec(substr($hex,2,2)) - round(255*$percent/100) );
    $b = max( 0, hexdec(substr($hex,4,2)) - round(255*$percent/100) );
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// ================================================================
// SETUP
// ================================================================
function swv_setup() {
    load_theme_textdomain( 'seliweb-view', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'custom-header', array(
        'default-image'      => '',
        'width'              => 1920,
        'height'             => 200,
        'flex-width'         => true,
        'flex-height'        => true,
        'header-text'        => true,
        'default-text-color' => 'ffffff',
    ) );

    register_nav_menus( array(
        'primary'  => __( 'Menu principal',          'seliweb-view' ),
        'footer-1' => __( 'Pied de page — colonne 1','seliweb-view' ),
        'footer-2' => __( 'Pied de page — colonne 2','seliweb-view' ),
        'footer-3' => __( 'Pied de page — colonne 3','seliweb-view' ),
    ) );

    register_sidebar( array(
        'name'          => __( 'Sidebar annonces', 'seliweb-view' ),
        'id'            => 'swv-sidebar',
        'description'   => __( 'Widgets affichés à droite des annonces.', 'seliweb-view' ),
        'before_widget' => '<div class="swv-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="swv-widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'after_setup_theme', 'swv_setup' );

// ================================================================
// SCRIPTS & STYLES
// ================================================================
function swv_enqueue() {
    wp_enqueue_style(
        'swv-fonts',
        'https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Source+Sans+3:wght@400;600&display=swap',
        array(), null
    );
    wp_enqueue_style( 'swv-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version') );
}
add_action( 'wp_enqueue_scripts', 'swv_enqueue' );
