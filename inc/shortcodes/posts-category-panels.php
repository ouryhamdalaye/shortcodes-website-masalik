<?php
// Facultatif mais propre : un namespace pour éviter les collisions
namespace ChildTheme\Shortcodes;

if ( ! function_exists( 'add_shortcode' ) ) {
    return; // sécurité si WP n'a pas fini de charger
}

add_action('init', function () {
	// Shortcode: [category_panels categories="slug-de-categorie1, slug-de-categorie2,..." posts="2" exclude="slug-a,slug-b"]
	add_shortcode('category_panels', function ($atts) {
		$a = shortcode_atts([
			'categories' => '',
			'posts'      => 6,
			'exclude' 	 => ''
		], $atts);

		if (empty($a['categories'])) {
			return '<p>Veuillez préciser l’attribut categories="slug-de-categorie1, slug-de-categorie2".</p>';
		}
		
		// Exclusions par slug
		$exclude_slugs = $a["exclude"];
		$exclude_slugs_array = [];
		if ( ! empty( $exclude_slugs ) ) {
			// explode + trim de chaque slug, suppression des vides
			$exclude_slugs_array = array_filter(array_map('trim', explode(',', $exclude_slugs)));
		}
		// La catégorie "uncategorized" toujours exclue
		$uncat = get_term_by('slug', 'uncategorized', 'category');
		$uncat_id = ($uncat && ! is_wp_error($uncat)) ? (int) $uncat->term_id : 0;
		
		$exclude_term_ids = [];
		if ( $uncat_id > 0 ) {
			$exclude_term_ids[] = $uncat_id;
		}

		if ( ! empty( $exclude_slugs_array ) ) {
			$terms = get_terms([
				'taxonomy'   => 'category',
				'slug'       => $exclude_slugs_array,
				'hide_empty' => false,
				'fields'     => 'ids',
			]);

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$exclude_term_ids = array_values(array_unique(array_merge($exclude_term_ids, $terms)));
			}
		}

		if (trim(strtolower($a['categories'])) === 'all') {
			$terms = get_terms([
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'parent'     => 0,           // top-level uniquement
				'orderby'    => 'name',
				'order'      => 'ASC',
				'exclude'    => $exclude_term_ids,
			]);
			if (empty($terms) || is_wp_error($terms)) {
				return '<p>Aucune catégorie trouvée.</p>';
			}
		} else {
			// Slugs nettoyés
			$slugs = array_filter(array_map(function ($s) {
				return sanitize_title(trim($s));
			}, explode(',', $a['categories'])));

			if (empty($slugs)) return '<p>Aucun slug valide fourni.</p>';

			// Récupère les termes
			$terms = [];
			foreach ($slugs as $slug) {
				$t = get_term_by('slug', $slug, 'category');
				if ($t && !is_wp_error($t)) $terms[] = $t;
			}
			if (empty($terms)) return '<p>Catégories introuvables.</p>';
		}

		ob_start(); ?>
	<section class="category-panels">
		<div class="cp-grid">
			<?php foreach ($terms as $term):
				$raw = function_exists('get_field') ? get_field('term_icon', 'category_'.$term->term_id) : null;
				$iconHtml = '';
				$imgUrl = '';

				if (is_array($raw)) {
					// Return format = Array (ACF peut donner 'ID' ou 'id' + 'url')
					if (!empty($raw['url']))  $imgUrl = $raw['url'];
				} else if (is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL)) {
					$imgUrl = $raw;                                // Return format = URL
				} else {
					$imgUrl = '';
				}

				if ($imgUrl) {
					$iconHtml = sprintf(
						'<span class="cp-icon" style="--icon-url:url(\'%s\')"></span>',
						esc_url($imgUrl)
					);
				}

				// get Hub URL
				$raw_hub = function_exists('get_field') ? get_field('lien_hub', 'category_'.$term->term_id) : null;
				$hubUrl = '#';
				if (is_array($raw_hub)) {
					// Return format = Array (ACF peut donner 'ID' ou 'id' + 'url')
					if (!empty($raw_hub['url']))  $hubUrl = $raw_hub['url'];
				} else if (is_string($raw_hub) && filter_var($raw_hub, FILTER_VALIDATE_URL)) {
					$hubUrl = $raw_hub;
				}else{
					$hubUrl = '#';
				}
			?>
			<article  class="cp-card">
				<header class="cp-head">
					<?php if ($iconHtml): ?>
					<?php echo $iconHtml; ?>
					<?php endif; ?>
					<h2 class="cp-title"><?php echo esc_html($term->name); ?></h2>
					<?php if (!empty($term->description)): ?>
					<p class="cp-desc"><?php echo esc_html($term->description); ?></p>
					<?php endif; ?>
				</header>
				<p class="cp-more">
					<a href="<?php echo $hubUrl ?>" class="cp-more-link">
						<?php _e('Découvrir','masalik'); ?><span style="margin-left:5px;"> ></span>
					</a>
				</p>
			</article>
			  <?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
	});
});