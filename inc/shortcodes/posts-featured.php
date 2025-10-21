<?php

// Facultatif mais propre : un namespace pour éviter les collisions
namespace ChildTheme\Shortcodes;

if ( ! function_exists( 'add_shortcode' ) ) {
    return; // sécurité si WP n'a pas fini de charger
}

add_action('init', function () {
    if ( ! function_exists('wc_get_products') ) {
        return;
    }

	// [featured_articles count="3" orderby="date" order="DESC" category="" offset="0"]
	add_shortcode('featured_articles', function ($atts) {
	  $a = shortcode_atts([
		'count'    => 3,
		'orderby'  => 'date',
		'order'    => 'DESC',
		'category' => '',      // slug de catégorie parente (optionnel)
		'offset'   => 0,
	  ], $atts);

	  // Filtre ACF: featured == true
	  $meta_query = [[
		'key'     => 'featured',
		'value'   => '1',          // ACF True/False enregistre '1'
		'compare' => '=',
	  ]];

	  // Si on veut restreindre à une catégorie parente donnée
	  $tax_query = [];
	  if (!empty($a['category'])) {
		$parent = get_term_by('slug', sanitize_title($a['category']), 'category');
		if ($parent && !is_wp_error($parent)) {
		  // Inclure tous les posts de la parent et de ses enfants
		  $tax_query[] = [
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => (int) $parent->term_id,
			'include_children' => true,
		  ];
		}
	  }

	  $q = new \WP_Query([
		'post_type'      => 'post',
		'posts_per_page' => (int) $a['count'],
		'orderby'        => sanitize_key($a['orderby']),
		'order'          => strtoupper($a['order']) === 'ASC' ? 'ASC' : 'DESC',
		'meta_query'     => $meta_query,
		'tax_query'      => !empty($tax_query) ? $tax_query : null,
		'offset'         => (int) $a['offset'],
		'no_found_rows'  => true,
	  ]);

	  if (!$q->have_posts()) return '<p>Aucun article à la une pour le moment.</p>';

	  ob_start(); ?>
	  <section class="feat-articles">
		<div class="fa-grid">
		  <?php while ($q->have_posts()): $q->the_post(); ?>
			<?php
			// 1) Catégorie parente (ou cat directe si pas de parent)
			$badge = '';
			$cats = get_the_category(get_the_ID());
			if (!empty($cats)) {
			  // on prend la première catégorie “principale”
			  $c = $cats[0];
			  if (!empty($c->parent)) {
				$parent = get_term($c->parent, 'category');
				if ($parent && !is_wp_error($parent)) $badge = $parent->name;
			  } else {
				$badge = $c->name;
			  }
			}

			// 2) Meta ACF
			$author    = function_exists('get_field') ? (string) get_field('author') : '';
			$read_time = function_exists('get_field') ? get_field('read_time') : '';
			$read_time = $read_time !== '' ? $read_time : ''; // number ou texte

			// 3) Extrait court
			$excerpt = wp_strip_all_tags(get_the_excerpt());
			$excerpt = mb_strimwidth($excerpt, 0, 180, '…', 'UTF-8');
			?>
			<article class="fa-card bg-tertiary rounded-xl p-6 hover">
			  <?php if ($badge): ?>
				<span class="fa-badge"><?php echo esc_html($badge); ?></span>
			  <?php endif; ?>

			  <h3 class="fa-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			  </h3>

			  <?php if ($excerpt): ?>
				<p class="fa-excerpt"><?php echo esc_html($excerpt); ?></p>
			  <?php endif; ?>

			  <div class="fa-meta">
				<?php if ($author): ?>
				  <span class="fa-author"><?php echo esc_html($author); ?></span>
				<?php endif; ?>
				<?php if ($read_time !== ''): ?>
				  <span class="fa-dot" aria-hidden="true">•</span>
				  <span class="fa-read"><?php echo esc_html($read_time); ?> min</span>
				<?php endif; ?>
			  </div>
			  <div class="fa-read-more">
				  <a href="<?php the_permalink(); ?>">Lire plus</a>
			 </div>
			</article>
		  <?php endwhile; wp_reset_postdata(); ?>
		</div>
	  </section>
	  <?php
	  return ob_get_clean();
	});
});