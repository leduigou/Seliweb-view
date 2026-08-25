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
        'color_primary' => '#1d6a4a',
    );
    $d = $default !== null ? $default : ( $defaults[ $key ] ?? null );
    return get_theme_mod( 'swv_' . $key, $d );
}

// Raccourci utilisé dans les templates
function swv_color() { return swv_opt('color_primary'); }

// ================================================================
// CUSTOMIZER
// ================================================================
function swv_add_color_setting( $wp_customize, $id, $label, $default, $description = '' ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $args = array( 'label' => $label, 'section' => 'colors' );
    if ( $description ) $args['description'] = $description;
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
}

function swv_customizer( $wp_customize ) {
    $derive = __( 'Par défaut, dérivée automatiquement de la couleur du bandeau.', 'seliweb-view' );

    swv_add_color_setting( $wp_customize, 'swv_color_primary',   __( 'Couleur du bandeau', 'seliweb-view' ), '#1d6a4a' );
    swv_add_color_setting( $wp_customize, 'swv_color_nav_bg',    __( 'Couleur — Navigation principale', 'seliweb-view' ), '', $derive );
    swv_add_color_setting( $wp_customize, 'swv_color_footer_bg', __( 'Couleur du pied de page', 'seliweb-view' ), '', $derive );
    swv_add_color_setting( $wp_customize, 'swv_color_nav_text',  __( 'Couleur du texte des menus', 'seliweb-view' ), '' );
    swv_add_color_setting( $wp_customize, 'swv_color_h1',        __( 'Couleur des titres H1', 'seliweb-view' ), '' );
    swv_add_color_setting( $wp_customize, 'swv_color_h2',        __( 'Couleur des titres H2', 'seliweb-view' ), '' );
}
add_action( 'customize_register', 'swv_customizer' );

// ================================================================
// CSS DYNAMIQUE — injecte la couleur choisie dans des variables CSS
// ================================================================
function swv_dynamic_css() {
    $color  = swv_color();
    $dark   = swv_darken_hex( $color, 20 );

    $nav_bg = get_theme_mod( 'swv_color_nav_bg', '' );
    if ( ! $nav_bg ) $nav_bg = $dark;

    $footer_bg = get_theme_mod( 'swv_color_footer_bg', '' );
    if ( ! $footer_bg ) $footer_bg = $dark;

    $nav_text = get_theme_mod( 'swv_color_nav_text', '' );
    if ( ! $nav_text ) $nav_text = 'rgba(255,255,255,.85)';

    $h1_color = get_theme_mod( 'swv_color_h1', '' );
    if ( ! $h1_color ) $h1_color = $color;

    $h2_color = get_theme_mod( 'swv_color_h2', '' );
    if ( ! $h2_color ) $h2_color = '#222';

    $header_textcolor = get_header_textcolor();
    $header_text = ( $header_textcolor && $header_textcolor !== 'blank' ) ? '#' . $header_textcolor : 'var(--color-white)';

    echo '<style id="swv-dynamic-css">
    :root {
        --color-primary:     ' . esc_attr($color)       . ';
        --color-primary-dk:  ' . esc_attr($dark)         . ';
        --color-header-bg:   ' . esc_attr($color)        . ';
        --color-footer-bg:   ' . esc_attr($footer_bg)    . ';
        --color-nav-bg:      ' . esc_attr($nav_bg)       . ';
        --color-nav-text:    ' . esc_attr($nav_text)     . ';
        --color-header-text: ' . esc_attr($header_text)  . ';
        --color-h1:          ' . esc_attr($h1_color)     . ';
        --color-h2:          ' . esc_attr($h2_color)     . ';
    }
    </style>' . "\n";
}
add_action( 'wp_head', 'swv_dynamic_css' );

// Mise à jour temps réel dans le Customizer (postMessage)
// Attaché en dépendance de 'customize-preview' (plutôt qu'à wp_footer) pour
// garantir que wp.customize existe déjà quand ce code s'exécute.
function swv_customizer_live() {
    $js = "
    function swvBindColor(id, vars){
        wp.customize(id, function(v){
            v.bind(function(color){
                var styleId = 'swv-live-' + id;
                var style = document.getElementById(styleId);
                if (!style) { style = document.createElement('style'); style.id = styleId; document.head.appendChild(style); }
                if (!color) return;
                var css = ':root {';
                vars.forEach(function(name){ css += name + ':' + color + ';'; });
                css += '}';
                style.textContent = css;
            });
        });
    }
    swvBindColor('swv_color_primary',   ['--color-primary', '--color-header-bg']);
    swvBindColor('swv_color_nav_bg',    ['--color-nav-bg']);
    swvBindColor('swv_color_footer_bg', ['--color-footer-bg']);
    swvBindColor('swv_color_nav_text',  ['--color-nav-text']);
    swvBindColor('swv_color_h1',        ['--color-h1']);
    swvBindColor('swv_color_h2',        ['--color-h2']);
    ";
    wp_add_inline_script( 'customize-preview', $js );
}
add_action( 'customize_preview_init', 'swv_customizer_live' );

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
        'height'      => 96,
        'width'       => 240,
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
