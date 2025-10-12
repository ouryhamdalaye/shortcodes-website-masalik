<?php
/**
 * Shortcode: [wc_cats_icons categories="all" exclude="slug-a,slug-b" parent="0" hide_empty="yes"]
 *
 * - categories : "all" (par défaut), vide, ou liste de slugs séparés par virgules
 * - exclude    : slugs à exclure (appliqué même quand categories="all")
 * - parent     : ID parent (0 = racine). Laisse vide pour toutes (hiérarchie plate).
 * - hide_empty : yes/no (par défaut yes)
 */
if ( ! defined('ABSPATH') ) { exit; }

if ( ! function_exists('add_shortcode') ) {
    return;
}

add_action('init', function () {
    add_shortcode('wc_cats_icons', function( $atts ) {
        if ( ! taxonomy_exists('product_cat') ) {
            return '<p>WooCommerce n\'est pas actif.</p>';
        }

        $a = shortcode_atts([
            'categories' => 'all',
            'exclude'    => '',
            'parent'     => '',
            'hide_empty' => 'yes',
            'class'      => '',
        ], $atts, 'wc_cats_icons');

        // Normalise les listes de slugs
        $wanted_slugs  = array_filter( array_map('trim', explode(',', (string)$a['categories'])) );
        $exclude_slugs = array_filter( array_map('trim', explode(',', (string)$a['exclude'])) );

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => ( strtolower($a['hide_empty']) !== 'no' ),
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];

        // Parent (optionnel)
        if ($a['parent'] !== '' && is_numeric($a['parent'])) {
            $args['parent'] = (int) $a['parent'];
        }

        // Exclusions par slug
        if ( ! empty($exclude_slugs) ) {
            // on récupère les IDs à exclure à partir des slugs
            $exclude_terms = get_terms([
                'taxonomy' => 'product_cat',
                'slug'     => $exclude_slugs,
                'hide_empty' => false,
                'fields'   => 'ids',
            ]);
            if ( ! is_wp_error($exclude_terms) && ! empty($exclude_terms) ) {
                $args['exclude'] = $exclude_terms;
            }
        }

        // Filtrage inclusif : liste de slugs spécifique
        $listing_all = empty($wanted_slugs) || strtolower(trim($a['categories'])) === 'all';
        if ( ! $listing_all ) {
            $args['slug'] = $wanted_slugs;
            // Respecter l'ordre fourni
            $args['orderby'] = 'include';
        }

        $terms = get_terms( $args );
        if ( is_wp_error($terms) || empty($terms) ) {
            return '<p>Aucune catégorie trouvée.</p>';
        }

        // Build HTML
        ob_start(); ?>
        <div class="gcid-cats-wrap <?php echo esc_attr($a['class']); ?>">
            <?php foreach ( $terms as $term ) :
                $thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                // Image de la catégorie (icone)
                if ( $thumb_id ) {
                    $img = wp_get_attachment_image( $thumb_id, 'medium', false, ['class'=>'gcid-cat-icon-img','alt'=>esc_attr($term->name)] );
                } else {
                    // Fallback (placeholder Woo)
                    $src = function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_thumbnail') : includes_url('images/media/default.png');
                    $img = '<img class="gcid-cat-icon-img" src="'.esc_url($src).'" alt="'.esc_attr($term->name).'">';
                }

                $link = get_term_link( $term );
                if ( is_wp_error($link) ) { $link = '#'; }
            ?>
                <a class="gcid-cat" href="<?php echo esc_url($link); ?>" title="<?php echo esc_attr($term->name); ?>">
                    <span class="gcid-cat-icon" aria-hidden="true"><?php echo $img; ?></span>
                    <span class="gcid-cat-label"><?php echo esc_html($term->name); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    });
});
