<?php
namespace ChildTheme\Shortcodes;

// Facultatif mais propre : un namespace pour éviter les collisions
namespace ChildTheme\Shortcodes;

if ( ! function_exists( 'add_shortcode' ) ) {
    return; // sécurité si WP n'a pas fini de charger
}

// On ne déclare le shortcode que si WooCommerce est là
add_action('init', function () {
    if ( ! function_exists('wc_get_products') ) {
        return;
    }
	// === [wc_book_of_month] : hero "Livre du mois" basé sur la catégorie ===
	add_shortcode('wc_book_of_month', function($atts){
		if ( ! function_exists('wc_get_products') ) return '';

		$a = shortcode_atts([
			'category'      => 'livre-du-moment', // slug de la catégorie
			'title'         => 'Le livre du mois',
			'subtitle'      => 'Coup de cœur des Éditions Tawhid',
			'btn_text'      => "Ça m'intéresse →",
			'bg'            => 'var(--gcid-edlabnnszn)', // fond du hero
			'title_color'   => 'var(--gcid-edlabnnszn)', // couleur du titre
			'text_color'    => 'var(--gcid-edlabnnszn)', // couleur de texte
			'btn_color'     => 'var(--gcid-secondary-color)', // couleur du bouton
			'img_size'      => 'large',   // taille WP de l’image
		], $atts, 'wc_book_of_month');

		// Récupère le produit le plus récent de la catégorie
		$products = wc_get_products([
			'status'            => 'publish',
			'limit'             => 1,
			'orderby'           => 'date',
			'order'             => 'DESC',
			'category'          => [ sanitize_title($a['category']) ],
			'category_operator' => 'IN',
			'return'            => 'objects',
		]);

		if (empty($products)) {
			return ''; // Rien à afficher si pas de produit
		}

		$product   = $products[0];
		$id        = $product->get_id();
		$permalink = get_permalink($id);
		$title     = $product->get_name();
		$price     = $product->get_price_html();
		$desc      = $product->get_short_description();
		if (empty($desc)) {
			// fallback : un extrait de la description longue
			$desc = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 55, '…' );
		}

		// Image produit
		$img_html = $product->get_image($a['img_size'], ['class' => 'wbom__img'], false);
		if (empty($img_html)) {
			$img_html = '<div class="wbom__img wbom__img--placeholder" aria-hidden="true"></div>';
		}

		// Sortie HTML
		ob_start(); ?>
		<section class="wbom" style="--wbom-bg: <?php echo esc_attr($a['bg']); ?>; --wbom-title-color: <?php echo esc_attr($a['title_color']); ?>; --wbom-text-color: <?php echo esc_attr($a['text_color']); ?>; --wbom-btn-color: <?php echo esc_attr($a['btn_color']); ?>;">
			<div class="wbom__head">
				<h2 class="wbom__kicker"><?php echo esc_html($a['title']); ?></h2>
				<?php if (!empty($a['subtitle'])): ?>
					<p class="wbom__sub"><?php echo esc_html($a['subtitle']); ?></p>
				<?php endif; ?>
			</div>

			<div class="wbom__grid">
				<a class="wbom__media" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
					<?php echo $img_html; ?>
				</a>
				<div class="wbom__body">
					<h3 class="wbom__title"><?php echo esc_html($title); ?></h3>
					<?php if ($desc): ?>
						<div class="wbom__excerpt"><?php echo wp_kses_post( wpautop($desc) ); ?></div>
					<?php endif; ?>
					<?php if ($price): ?>
						<div class="wbom__price"><?php echo wp_kses_post($price); ?></div>
					<?php endif; ?>
					<p>
						<a class="wbom__btn" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($a['btn_text']); ?></a>
					</p>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	});
});
