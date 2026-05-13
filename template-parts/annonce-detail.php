<?php
/**
 * template-parts/annonce-detail.php
 * Reçoit : $args['annonce_id'], $args['groupe_visiteur']
 */
if ( ! class_exists('Seliweb_Annonces') ) return;

global $wpdb;
$annonce_id      = intval( $args['annonce_id'] ?? 0 );
$groupe_visiteur = $args['groupe_visiteur'] ?? null;

$ta   = $wpdb->prefix . 'seliweb_annonces';
$tc   = $wpdb->prefix . 'seliweb_categories';
$tr   = $wpdb->prefix . 'seliweb_rubriques';
$ts   = $wpdb->prefix . 'seliweb_statuts';
$tm   = $wpdb->prefix . 'seliweb_membres';

$detail = $wpdb->get_row( $wpdb->prepare(
    "SELECT a.* FROM $ta a WHERE a.id=%d",
    $annonce_id
) );

if ( $detail ) {
    // Ajouter les infos liées séparément
    $detail->cat_nom = $wpdb->get_var( $wpdb->prepare(
        "SELECT nom FROM $tc WHERE id = %d",
        intval( $detail->categorie_id )
    ) );
    $detail->rub_nom = $wpdb->get_var( $wpdb->prepare(
        "SELECT nom FROM $tr WHERE id = %d",
        intval( $detail->rubrique_id )
    ) );
    $detail->statut_slug = $wpdb->get_var( $wpdb->prepare(
        "SELECT slug FROM $ts WHERE id = %d",
        intval( $detail->statut_id )
    ) );
    $membre = $wpdb->get_row( $wpdb->prepare(
        "SELECT telephone, adresse, ville FROM $tm WHERE id = %d",
        intval( $detail->membre_id )
    ) );
    if ( $membre ) {
        $detail->telephone = $membre->telephone;
        $detail->adresse = $membre->adresse;
        $detail->ville = $membre->ville;
    }
    $detail->user_email = $wpdb->get_var( $wpdb->prepare(
        "SELECT user_email FROM {$wpdb->users} WHERE ID = (SELECT wp_user_id FROM $tm WHERE id = %d)",
        intval( $detail->membre_id )
    ) );
    $detail->membre_nom = $wpdb->get_var( $wpdb->prepare(
        "SELECT display_name FROM {$wpdb->users} WHERE ID = (SELECT wp_user_id FROM $tm WHERE id = %d)",
        intval( $detail->membre_id )
    ) );
}

$detail_ok = $detail && ( $detail->statut_slug !== 'expire' );

if ( ! $detail_ok ) {
    ?>
<main id="swv-main">
    <div class="swv-single-wrap">
        <p class="swv-annonces-empty"><?php esc_html_e('Annonce introuvable ou expirée.','seliweb-view'); ?></p>
    </div>
</main>
<?php return; } endif;

$prix    = Seliweb_Annonces::get_prix( $annonce_id );
$page_url = swv_annonces_page_url();

// Traitement envoi message
if ( isset( $_GET['seliweb_message_envoye'] ) ) :
    echo '<div style="background:#edfaef;border-left:4px solid #1d6a4a;padding:10px 14px;margin:12px 20px;border-radius:4px;font-size:.9rem;">'
       . esc_html__('Votre message a été envoyé.','seliweb-view') . '</div>';
endif;
?>

<main id="swv-main">
    <div class="swv-main-inner">

        <div class="swv-detail-wrap">

            <a href="<?php echo esc_url($page_url); ?>" class="swv-detail-back">
                &larr; <?php esc_html_e('Retour aux annonces','seliweb-view'); ?>
            </a>

            <h1 style="font-family:var(--font-title);margin-bottom:12px;">
                <?php echo esc_html($detail->titre); ?>
            </h1>

            <!-- Tags -->
            <div class="swv-card-tags" style="margin-bottom:16px;">
                <span class="swv-tag swv-tag-cat"><?php echo esc_html($detail->cat_nom); ?></span>
                <?php if ($detail->cat_slug==='annonces' && $detail->type_annonce) : ?>
                    <span class="swv-tag swv-tag-type"><?php echo esc_html(ucfirst($detail->type_annonce)); ?></span>
                <?php endif; ?>
                <?php if ($detail->rub_nom) : ?>
                    <span class="swv-tag swv-tag-rubrique"><?php echo esc_html($detail->rub_nom); ?></span>
                <?php endif; ?>
                <?php if ($detail->statut_slug==='urgent') : ?>
                    <span class="swv-card-urgent"><?php esc_html_e('URGENT','seliweb-view'); ?></span>
                <?php endif; ?>
            </div>

            <!-- Photos -->
            <?php if ($detail->photo1 || $detail->photo2) : ?>
            <div class="swv-detail-photos">
                <?php if ($detail->photo1) echo '<img src="'.esc_url($detail->photo1).'" alt="">'; ?>
                <?php if ($detail->photo2) echo '<img src="'.esc_url($detail->photo2).'" alt="">'; ?>
            </div>
            <?php endif; ?>

            <!-- Texte -->
            <div class="swv-detail-texte">
                <?php echo nl2br(esc_html($detail->texte)); ?>
            </div>

            <!-- Prix -->
            <?php if ($detail->est_don) : ?>
                <p style="font-weight:700;color:var(--color-primary);margin-bottom:16px;">
                    <?php esc_html_e('Don','seliweb-view'); ?>
                </p>
            <?php elseif (!empty($prix)) : ?>
                <p style="font-weight:700;color:var(--color-primary-dk);margin-bottom:16px;">
                    <?php foreach($prix as $p) echo esc_html($p->prix.' '.($p->symbole?:$p->nom)).' '; ?>
                </p>
            <?php endif; ?>

            <!-- Contact -->
            <div class="swv-detail-contact">
                <h4><?php esc_html_e('Contacter l\'annonceur','seliweb-view'); ?></h4>

                <?php if (!is_user_logged_in()) : ?>
                    <p><em>
                        <?php printf(
                            wp_kses(__('<a href="%s">Connectez-vous</a> pour contacter l\'annonceur.','seliweb-view'), array('a'=>array('href'=>array()))),
                            esc_url(wp_login_url(get_permalink()))
                        ); ?>
                    </em></p>

                <?php elseif (!$groupe_visiteur) : ?>
                    <p><em><?php esc_html_e('Vous n\'êtes rattaché à aucun groupe.','seliweb-view'); ?></em></p>

                <?php else : ?>

                    <?php if ($groupe_visiteur->contact_mail_cache && !isset($_GET['seliweb_message_envoye'])) : ?>
                    <form method="post" action="<?php echo esc_url($page_url); ?>" style="margin-bottom:12px;">
                        <?php wp_nonce_field('seliweb_contact_'.$annonce_id,'seliweb_contact_nonce'); ?>
                        <input type="hidden" name="annonce_id" value="<?php echo intval($annonce_id); ?>">
                        <textarea name="message" rows="4" required
                                  style="width:100%;max-width:480px;padding:8px;border:1px solid #ccc;border-radius:4px;font-family:inherit;font-size:.9rem;margin-bottom:8px;display:block;"
                                  placeholder="<?php esc_attr_e('Votre message…','seliweb-view'); ?>"></textarea>
                        <button type="submit" name="seliweb_envoyer_message"
                                style="padding:8px 18px;background:var(--color-primary);color:#fff;border:none;border-radius:4px;cursor:pointer;font-family:inherit;">
                            <?php esc_html_e('Envoyer un message','seliweb-view'); ?>
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($groupe_visiteur->contact_mail_visible && $detail->user_email) : ?>
                        <p><?php esc_html_e('Email :','seliweb-view'); ?>
                            <a href="mailto:<?php echo esc_attr($detail->user_email); ?>"><?php echo esc_html($detail->user_email); ?></a>
                        </p>
                    <?php endif; ?>
                    <?php if ($groupe_visiteur->contact_tel && $detail->telephone) : ?>
                        <p><?php esc_html_e('Téléphone :','seliweb-view'); ?> <?php echo esc_html($detail->telephone); ?></p>
                    <?php endif; ?>
                    <?php if ($groupe_visiteur->contact_adresse && $detail->adresse) : ?>
                        <p><?php esc_html_e('Adresse :','seliweb-view'); ?> <?php echo esc_html($detail->adresse.($detail->ville?' — '.$detail->ville:'')); ?></p>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </div>

        <!-- Sidebar -->
        <aside id="swv-sidebar">
            <?php if (is_active_sidebar('swv-sidebar')) dynamic_sidebar('swv-sidebar'); ?>
        </aside>

    </div>
</main>
