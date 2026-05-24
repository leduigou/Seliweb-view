<?php
/**
 * Seliweb View — functions.php
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
function swv_per_page() {
    global $wpdb;
    $val = $wpdb->get_var( "SELECT valeur FROM {$wpdb->prefix}seliweb_parametres WHERE cle='annonces_par_page' LIMIT 1" );
    return ( $val !== null && intval( $val ) > 0 ) ? intval( $val ) : 12;
}
function swv_color()        { return swv_opt('color_primary'); }

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
    // Calcul d'une version plus foncée (-20% luminosité environ)
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

// ================================================================
// FILTRE MENU PRINCIPAL
// — Masque Connexion et Inscription en permanence
// — Masque Mon Compte si non connecté
// ================================================================
function swv_filter_primary_menu( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) {
        return $items;
    }
    global $wpdb;

    $login_id = (int) $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_status='publish' AND post_type='page'
           AND post_content LIKE '%seliweb_login%' LIMIT 1"
    );
    $compte_id = (int) $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_status='publish' AND post_type='page'
           AND post_content LIKE '%seliweb_mon_compte%' LIMIT 1"
    );
    $inscription = get_page_by_path( 'inscription-sel' );
    $inscription_id = $inscription ? (int) $inscription->ID : 0;

    $logged_in = is_user_logged_in();

    foreach ( $items as $key => $item ) {
        $page_id = (int) $item->object_id;
        // Toujours masquer Connexion et Inscription
        if ( $login_id      && $page_id === $login_id )      { unset( $items[$key] ); continue; }
        if ( $inscription_id && $page_id === $inscription_id ) { unset( $items[$key] ); continue; }
        // Masquer Mon Compte si non connecté
        if ( $compte_id && $page_id === $compte_id && ! $logged_in ) { unset( $items[$key] ); }
    }

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'swv_filter_primary_menu', 10, 2 );

// ================================================================
// HELPERS TEMPLATE
// ================================================================
function swv_annonces_page_url() {
    static $url = null;
    if ( $url ) return $url;
    global $wpdb;
    $page_id = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_status='publish' AND post_type='page'
           AND post_content LIKE '%seliweb_annonces%' LIMIT 1"
    );
    $url = $page_id ? get_permalink( $page_id ) : home_url('/');
    return $url;
}

function swv_page_url( $num ) {
    $params = $_GET;
    unset( $params['seliweb_annonce'] );
    $params['sel_page'] = $num;
    return add_query_arg( array_map( 'urlencode', $params ), swv_annonces_page_url() );
}

function swv_render_pagination( $page_courante, $nb_pages, $total, $top = false ) {
    if ( $total < 1 ) return;
    if ( ! $top && $nb_pages < 2 ) return;
    ?>
    <div class="swv-pagination-bar<?php echo $top ? ' swv-pagination-bar-top' : ' swv-pagination-bar-bottom'; ?>">
        <div class="swv-pagination-bar-inner">

            <span class="swv-page-info">
                <?php printf(
                    esc_html( _n('%d annonce','%d annonces',$total,'seliweb-view') ),
                    $total
                ); ?>
                &nbsp;&mdash;&nbsp;
                <?php printf( esc_html__('Page %1$d / %2$d','seliweb-view'), $page_courante, $nb_pages ); ?>
            </span>

            <div class="swv-bar-controls">

                <?php if ( $top ) : ?>
                <div class="swv-vue-toggle">
                    <button type="button" id="swv-vue-liste" class="swv-vue-btn" title="<?php esc_attr_e('Vue liste','seliweb-view'); ?>">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><rect x="0" y="1" width="16" height="2" rx="1"/><rect x="0" y="7" width="16" height="2" rx="1"/><rect x="0" y="13" width="16" height="2" rx="1"/></svg>
                        <?php esc_html_e('Liste','seliweb-view'); ?>
                    </button>
                    <button type="button" id="swv-vue-grille" class="swv-vue-btn" title="<?php esc_attr_e('Vue colonnes','seliweb-view'); ?>">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><rect x="0" y="0" width="7" height="7" rx="1"/><rect x="9" y="0" width="7" height="7" rx="1"/><rect x="0" y="9" width="7" height="7" rx="1"/><rect x="9" y="9" width="7" height="7" rx="1"/></svg>
                        <?php esc_html_e('Colonnes','seliweb-view'); ?>
                    </button>
                </div>
                <?php endif; ?>

                <nav class="swv-pages-nav">
                    <?php if ( $page_courante > 1 ) : ?>
                        <a href="<?php echo esc_url(swv_page_url($page_courante-1)); ?>" class="swv-page-prev">&laquo; <?php esc_html_e('Préc.','seliweb-view'); ?></a>
                    <?php else : ?>
                        <span class="swv-page-prev disabled">&laquo; <?php esc_html_e('Préc.','seliweb-view'); ?></span>
                    <?php endif; ?>

                    <?php if ( ! $top && $nb_pages > 1 ) :
                        $shown = array();
                        for ($i=1; $i<=$nb_pages; $i++) {
                            if ($i===1 || $i===$nb_pages || ($i>=$page_courante-2 && $i<=$page_courante+2)) $shown[]=$i;
                        }
                        $prev=null;
                        foreach($shown as $n):
                            if ($prev!==null && $n>$prev+1) echo '<span class="swv-page-ellipsis">&hellip;</span>';
                            if ($n===$page_courante): ?>
                                <span class="swv-page-num current"><?php echo $n; ?></span>
                            <?php else: ?>
                                <a href="<?php echo esc_url(swv_page_url($n)); ?>" class="swv-page-num"><?php echo $n; ?></a>
                            <?php endif;
                            $prev=$n;
                        endforeach;
                    endif; ?>

                    <?php if ( $page_courante < $nb_pages ) : ?>
                        <a href="<?php echo esc_url(swv_page_url($page_courante+1)); ?>" class="swv-page-next"><?php esc_html_e('Suiv.','seliweb-view'); ?> &raquo;</a>
                    <?php else : ?>
                        <span class="swv-page-next disabled"><?php esc_html_e('Suiv.','seliweb-view'); ?> &raquo;</span>
                    <?php endif; ?>
                </nav>

            </div>
        </div>
    </div>
    <?php if ( $top ) : ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var KEY = 'seliweb_vue';
        var wrap = document.querySelector('#swv-annonces > div[class*="swv-annonces-"]');
        var btnL = document.getElementById('swv-vue-liste');
        var btnG = document.getElementById('swv-vue-grille');
        if (!wrap || !btnL || !btnG) return;

        function apply(vue) {
            wrap.className = wrap.className.replace(/swv-annonces-\w+/, 'swv-annonces-' + vue);
            btnG.classList.toggle('swv-vue-btn-actif', vue === 'grille');
            btnL.classList.toggle('swv-vue-btn-actif', vue === 'liste');
            try { localStorage.setItem(KEY, vue); } catch(e) {}
        }

        var pref;
        try { pref = localStorage.getItem(KEY); } catch(e) {}
        if (!pref) pref = wrap.className.indexOf('grille') !== -1 ? 'grille' : 'liste';
        apply(pref);

        btnL.addEventListener('click', function(){ apply('liste'); });
        btnG.addEventListener('click', function(){ apply('grille'); });
    });
    </script>
    <?php endif;
}

function swv_render_search( $filters = array() ) {
    if ( ! class_exists('Seliweb_Annonces') ) return;

    global $wpdb;
    $tc = $wpdb->prefix . 'seliweb_categories';
    $tr = $wpdb->prefix . 'seliweb_rubriques';

    $categories = $wpdb->get_results("SELECT * FROM $tc ORDER BY nom ASC");
    $rubriques  = $wpdb->get_results("SELECT * FROM $tr ORDER BY categorie_id, nom ASC");
    $villes     = Seliweb_Annonces::get_villes();
    $page_url   = swv_annonces_page_url();
    ?>
    <div id="swv-search">
        <div class="swv-search-inner">
            <form method="get" action="<?php echo esc_url($page_url); ?>" class="swv-search-form">

                <select name="categorie_id"
                        onchange="swvRubUpdate(this.value); swvTypeUpdate(this.value)">
                    <option value=""><?php esc_html_e('Toutes catégories','seliweb-view'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo intval($cat->id); ?>"
                                data-slug="<?php echo esc_attr($cat->slug); ?>"
                                <?php selected($filters['categorie_id']??0, $cat->id); ?>>
                            <?php echo esc_html($cat->nom); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="type_annonce" id="swv-sel-type"
                        style="<?php echo !empty($filters['categorie_id']) ? '' : 'display:none'; ?>">
                    <option value=""><?php esc_html_e('Offres & demandes','seliweb-view'); ?></option>
                    <option value="offre"   <?php selected($filters['type_annonce']??'','offre'); ?>><?php esc_html_e('Offres','seliweb-view'); ?></option>
                    <option value="demande" <?php selected($filters['type_annonce']??'','demande'); ?>><?php esc_html_e('Demandes','seliweb-view'); ?></option>
                </select>

                <select name="rubrique_id" id="swv-sel-rub">
                    <option value=""><?php esc_html_e('Toutes rubriques','seliweb-view'); ?></option>
                    <?php foreach ($rubriques as $rub): ?>
                        <option value="<?php echo intval($rub->id); ?>"
                                data-categorie="<?php echo intval($rub->categorie_id); ?>"
                                style="<?php echo (empty($filters['categorie_id']) || $rub->categorie_id==$filters['categorie_id']) ? '' : 'display:none'; ?>"
                                <?php selected($filters['rubrique_id']??0, $rub->id); ?>>
                            <?php
                            // FIX antislash : esc_html() au lieu de esc_js()
                            echo esc_html($rub->nom);
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($villes)): ?>
                <select name="ville">
                    <option value=""><?php esc_html_e('Toutes villes','seliweb-view'); ?></option>
                    <?php foreach ($villes as $ville): ?>
                        <option value="<?php echo esc_attr($ville); ?>"
                                <?php selected($filters['ville']??'', $ville); ?>>
                            <?php echo esc_html($ville); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <button type="submit" class="swv-search-btn">
                    <?php esc_html_e('Rechercher','seliweb-view'); ?>
                </button>
                <button type="button" class="swv-reset-btn"
                        onclick="window.location=<?php echo wp_json_encode($page_url); ?>">
                    <?php esc_html_e('Réinitialiser','seliweb-view'); ?>
                </button>

            </form>
        </div>
    </div>
    <script>
    function swvRubUpdate(catId){
        document.querySelectorAll('#swv-sel-rub option[data-categorie]').forEach(function(o){
            o.style.display=(!catId||o.dataset.categorie==catId)?'':'none';
        });
        document.getElementById('swv-sel-rub').value='';
    }
    function swvTypeUpdate(catId){
        var sel=document.querySelector('[name="categorie_id"]');
        var opt=sel?sel.options[sel.selectedIndex]:null;
        var isA=opt&&opt.dataset.slug==='annonces';
        document.getElementById('swv-sel-type').style.display=(catId&&isA)?'':'none';
    }
    </script>
    <?php
}

function swv_render_card($annonce, $mode=null) {
    if (!class_exists('Seliweb_Annonces')) return;
    if ($mode===null) $mode = swv_display_mode();

    $prix      = Seliweb_Annonces::get_prix($annonce->id);
    $has_statut = ( ! empty( $annonce->statut_slug ) && $annonce->statut_slug !== 'expire' );
    $url       = add_query_arg('seliweb_annonce',$annonce->id,swv_annonces_page_url());
    $date      = date_i18n(get_option('date_format'),strtotime($annonce->date_creation));

    if ($mode==='grille'): ?>
        <div class="swv-card">
            <div class="swv-card-photo">
                <?php if ($annonce->photo1): ?>
                    <a href="<?php echo esc_url($url); ?>">
                        <img src="<?php echo esc_url($annonce->photo1); ?>" alt="<?php echo esc_attr($annonce->titre); ?>">
                    </a>
                <?php else: ?>
                    <div class="swv-card-no-photo">&#128247;</div>
                <?php endif; ?>
            </div>
            <div class="swv-card-body">
                <div class="swv-card-id">#<?php echo intval($annonce->id); ?></div>
                <div class="swv-card-title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($annonce->titre); ?></a></div>
                <div class="swv-card-date"><?php echo esc_html($date); ?></div>
                <?php if ($has_statut): ?><span class="swv-card-statut"><?php echo esc_html($annonce->statut_nom); ?></span><?php endif; ?>
                <div class="swv-card-prix">
                    <?php if ($annonce->est_don): ?>
                        <span class="swv-card-don"><?php esc_html_e('Don','seliweb-view'); ?></span>
                    <?php elseif (!empty($prix)): ?>
                        <?php foreach($prix as $idx_p => $p): ?>
                            <?php if ($idx_p > 0): ?>
                                <span class="swv-card-prix-coord"><?php echo esc_html($p->coordination ?: 'OU'); ?></span>
                            <?php endif; ?>
                            <span class="swv-card-prix-item"><?php echo esc_html($p->prix.' '.($p->symbole?:$p->nom)); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: // liste ?>
        <div class="swv-card">
            <div class="swv-card-photo">
                <?php if ($annonce->photo1): ?>
                    <a href="<?php echo esc_url($url); ?>">
                        <img src="<?php echo esc_url($annonce->photo1); ?>" alt="<?php echo esc_attr($annonce->titre); ?>">
                    </a>
                <?php else: ?>
                    <div class="swv-card-no-photo"></div>
                <?php endif; ?>
            </div>
            <div class="swv-card-body">
                <div class="swv-card-id">#<?php echo intval($annonce->id); ?></div>
                <div class="swv-card-title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($annonce->titre); ?></a></div>
                <div class="swv-card-tags">
                    <span class="swv-tag swv-tag-cat"><?php echo esc_html($annonce->cat_nom); ?></span>
                    <?php if ($annonce->cat_slug==='annonces' && $annonce->type_annonce): ?>
                        <span class="swv-tag swv-tag-type"><?php echo esc_html(ucfirst($annonce->type_annonce)); ?></span>
                    <?php endif; ?>
                    <?php if ($annonce->rub_nom): ?>
                        <span class="swv-tag swv-tag-rubrique"><?php echo esc_html($annonce->rub_nom); ?></span>
                    <?php endif; ?>
                </div>
                <div class="swv-card-date"><?php echo esc_html($date); ?></div>
                <?php if ($has_statut): ?><span class="swv-card-statut"><?php echo esc_html($annonce->statut_nom); ?></span><?php endif; ?>
                <div class="swv-card-prix">
                    <?php if ($annonce->est_don): ?>
                        <span class="swv-card-don"><?php esc_html_e('Don','seliweb-view'); ?></span>
                    <?php elseif (!empty($prix)): ?>
                        <?php foreach($prix as $idx_p => $p): ?>
                            <?php if ($idx_p > 0): ?>
                                <span class="swv-card-prix-coord"><?php echo esc_html($p->coordination ?: 'OU'); ?></span>
                            <?php endif; ?>
                            <span class="swv-card-prix-item"><?php echo esc_html($p->prix.' '.($p->symbole?:$p->nom)); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif;
}
