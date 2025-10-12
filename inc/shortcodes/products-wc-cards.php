<?php
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
	// === Shortcode WooCommerce "cards grid" ===
	add_shortcode('wc_cards', function($atts){
		if ( ! function_exists('wc_get_products') ) return '';

		$a = shortcode_atts([
			'category'          => '',
			'per_page'          => 12,
			'order'             => 'DESC',
			'orderby'           => 'date',
			'columns'           => 3,
			'show_filter'       => 'false',
			'include_children'  => 'true',
		], $atts, 'wc_cards');

		// Catégorie sélectionnée via le filtre (GET) prioritaire
		$selected_cat = isset($_GET['wc_cards_cat']) ? sanitize_text_field($_GET['wc_cards_cat']) : '';
		$category     = $selected_cat ?: sanitize_text_field($a['category']);

		// Build query
		$args = [
			'status'  => 'publish',
			'limit'   => (int)$a['per_page'],
			'orderby' => $a['orderby'],
			'order'   => $a['order'],
		];

		if ( $category !== '' ) {
			$args['category'] = [$category];
			$args['category_operator'] = 'IN';
		}

		// Récupération produits
		$products = wc_get_products($args);

		// Récupération catégories pour le filtre (si demandé)
		$filter_html = '';
		if ( filter_var($a['show_filter'], FILTER_VALIDATE_BOOLEAN) ) {
			$terms = get_terms([
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
			]);

			if ( ! is_wp_error($terms) && ! empty($terms) ) {
				$current_url = esc_url(remove_query_arg(['wc_cards_cat']));
				$filter_html .= '<form class="wc-cards__filter" method="get" action="'. $current_url .'">';
				// Conserve les autres paramètres d'URL
				foreach ($_GET as $key=>$val) {
					if ($key === 'wc_cards_cat') continue;
					$filter_html .= '<input type="hidden" name="'. esc_attr($key) .'" value="'. esc_attr($val) .'">';
				}
				$filter_html .= '<label for="wc_cards_cat" class="screen-reader-text">Filtrer par catégorie</label>';
				$filter_html .= '<select id="wc_cards_cat" name="wc_cards_cat" onchange="this.form.submit()">';
				$filter_html .= '<option value="">Toutes les catégories</option>';
				foreach ($terms as $t) {
					$sel = selected($t->slug, $selected_cat ?: $a['category'], false);
					$filter_html .= '<option value="'. esc_attr($t->slug) .'" '. $sel .'>'. esc_html($t->name) .'</option>';
				}
				$filter_html .= '</select>';
				$filter_html .= '</form>';
			}
		}

		// Grid classes
		$cols = max(1, min(6, (int)$a['columns']));

		ob_start();
		?>
		<div class="wc-cards wc-cards--cols-<?php echo (int)$cols; ?>">
			<?php echo $filter_html; ?>

			<div class="wc-cards__grid">
				<?php if (empty($products)) : ?>
					<p class="wc-cards__empty">Aucun produit trouvé.</p>
				<?php else :
					foreach ($products as $product) :
						$id     = $product->get_id();
						$perma  = get_permalink($id);
						$title  = $product->get_name();

						// --- Drapeaux catégories
						$is_new   = has_term('nouveau', 'product_cat', $id);
						$is_promo = has_term('promo', 'product_cat', $id);

						// --- Prix : si promo + vrai prix remisé, on force del/ins
						if ( $is_promo && $product->get_regular_price() && $product->get_sale_price() ) {
							$price_html = '<span class="price">'
								. '<del>' . wc_price( $product->get_regular_price() ) . '</del> '
								. '<ins>' . wc_price( $product->get_sale_price() ) . '</ins>'
								. '</span>';
						} else {
							$price_html = $product->get_price_html(); // fallback WooCommerce
						}
						$img    = $product->get_image('woocommerce_thumbnail', ['class'=>'wc-cards__img'], false);
						$rating = wc_get_rating_html($product->get_average_rating());
						// Bouton Add to cart via hook WooCommerce
						if ( $product->is_type('variable') ) {
							// Lien vers la page produit avec texte "Choix des options"
							$btn = sprintf(
								'<a href="%s" class="button wc-cards__btn">%s</a>',
								esc_url( $perma ),
								esc_html__('Choix des options', 'woocommerce')
							);
						}else{
						$btn = apply_filters(
							'woocommerce_loop_add_to_cart_link',
							sprintf(
								'<a href="%s" data-quantity="1" class="button wc-cards__btn add_to_cart_button ajax_add_to_cart" data-product_id="%s" data-product_sku="%s" aria-label="%s" rel="nofollow">%s</a>',
								esc_url($product->add_to_cart_url()),
								esc_attr($id),
								esc_attr($product->get_sku()),
								esc_attr(sprintf(__('Ajouter “%s” au panier', 'woocommerce'), $product->get_name())),
								esc_html__('Ajouter', 'woocommerce')
							),
							$product
						);}
						?>
						<article class="wc-card">
							<a class="wc-card__media" href="<?php echo esc_url($perma); ?>" aria-label="<?php echo esc_attr($title); ?>">
								<?php echo $img ?: '<div class="wc-card__placeholder" aria-hidden="true"></div>'; ?>

								<?php
								// Badge (promo prioritaire sur nouveau)
								if ($is_promo) {
									echo '<span class="wc-card__badge wc-card__badge--promo">Promo</span>';
								} elseif ($is_new) {
									echo '<span class="wc-card__badge wc-card__badge--new">Nouveau</span>';
								}
								?>
							</a>
							<div class="wc-card__body">
								<h3 class="wc-card__title"><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></h3>
								<div class="wc-card__price"><?php echo wp_kses_post($price_html); ?></div>
								<?php 
								// --- SWATCHES COULEUR (pa_couleur)
								if ( $product->is_type('variable') ) {

									$attr_tax = 'pa_couleur';
									// --- Construire une map variation -> data (image, stock, prix HTML) par slug de couleur
									$var_map = [];

									foreach ( $product->get_children() as $vid ) {
										$v = wc_get_product( $vid );
										if ( ! $v ) continue;

										// slug de la couleur sur la variation (toujours en 'attribute_{pa_tax}')
										$slug = get_post_meta( $vid, 'attribute_' . $attr_tax, true );
										if ( ! $slug ) continue;

										$img_id  = $v->get_image_id();
										$img     = $img_id ? wp_get_attachment_image_src( $img_id, 'woocommerce_thumbnail' )[0] : '';
										$srcset  = $img_id ? wp_get_attachment_image_srcset( $img_id, 'woocommerce_thumbnail' ) : '';

										$regular = $v->get_regular_price();
										$sale    = $v->get_sale_price();
										$price_h = ($sale && (float)$sale > 0)
											? '<span class="price"><del>' . wc_price($regular) . '</del> <ins>' . wc_price($sale) . '</ins></span>'
											: '<span class="price">' . wc_price($v->get_price()) . '</span>';

										$var_map[$slug] = [
											'img'        => $img,
											'srcset'     => $srcset,
											'price_html' => $price_h,
											'in_stock'   => $v->is_in_stock(),
										];
									}

									// --- Termes disponibles pour l’attribut
									$terms = wp_get_post_terms( $id, $attr_tax, ['fields' => 'all'] );

									if ( ! is_wp_error($terms) && ! empty($terms) ) {
										// Dictionnaire fallback pour couleurs FR -> hex (si pas de meta 'color')
										$color_map = [
											'bleu' => '#2b6cb0','rouge' => '#e11d48','noir' => '#111827','gris' => '#6b7280',
											'blanc' => '#f9fafb','vert' => '#16a34a','jaune' => '#f59e0b','marron' => '#92400e',
											'rose' => '#ec4899','violet' => '#8b5cf6','orange' => '#f97316','beige' => '#f5f5dc',
											'dore' => '#d4af37','argent' => '#c0c0c0'
										];

										$max = 3; $shown = 0; $total = count($terms);
										echo '<div class="wc-card__swatches">';

										foreach ( $terms as $t ) {
											$slug  = $t->slug; // ex. 'bleu'
											$meta  = get_term_meta( $t->term_id, 'color', true ); // Smart Swatches (si défini)
											$key   = strtolower( remove_accents( $slug ) );
											$hex   = $meta ?: ( $color_map[$key] ?? '#999999' );

											$vd    = $var_map[$slug] ?? ['img'=>'','srcset'=>'','price_html'=>'','in_stock'=>false];
											$oos   = empty($vd['in_stock']); // rupture de stock ?

											if ( $shown < $max ) {
												echo '<button type="button"
														class="wc-swatch'. ( $oos ? ' is-oos' : '' ) .'"
														title="'. esc_attr($t->name) .'"
														'. ( $oos ? 'disabled aria-disabled="true"' : '' ) .'
														style="--swatch-color:'. esc_attr($hex) .';"
														data-variant="'. esc_attr($slug) .'"
														data-img="'. esc_url($vd['img']) .'"
														data-srcset="'. esc_attr($vd['srcset']) .'"
														data-pricehtml="'. esc_attr( $vd['price_html'] ) .'"></button>';
												$shown++;
											}
										}

										if ( $total > $max ) {
											echo '<span class="wc-swatch__more">+'. ($total - $max) .' de plus</span>';
										}
										echo '</div>';
									}
								}
								?>
								<?php if ($rating) : ?>
									<div class="wc-card__rating"><?php echo $rating; ?></div>
								<?php endif; ?>
							</div>
							<div class="wc-card__actions">
								<?php echo $btn; ?>
							</div>
						</article>
					<?php endforeach;
				endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	});
});