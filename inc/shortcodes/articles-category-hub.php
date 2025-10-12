<?php
// Facultatif mais propre : un namespace pour éviter les collisions
namespace ChildTheme\Shortcodes;

if ( ! function_exists( 'add_shortcode' ) ) {
    return; // sécurité si WP n'a pas fini de charger
}

add_action('init', function () {
	// Shortcode: [articles_category_hub parent="slug-du-parent" posts="2"]
	add_shortcode('articles_category_hub', function ($atts) {
		$a = shortcode_atts([
			'parent' => '',   // slug de la catégorie principale
			'posts'  => 2,    // nb d'articles par sous-catégorie
		], $atts);

		if (!$a['parent']) return '<p>Veuillez préciser l’attribut parent="slug-de-categorie".</p>';

		$parent = get_term_by('slug', sanitize_title($a['parent']), 'category');
		if (!$parent || is_wp_error($parent)) return '<p>Catégorie introuvable.</p>';

		// Récupère les sous-catégories directes
		$subs = get_terms([
			'taxonomy'   => 'category',
			'parent'     => (int) $parent->term_id,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]);
	
		if (empty($subs) || is_wp_error($subs)) return '<p>Oups… aucun article pour l’instant. Mais on prépare du contenu, restez connectés !</p>';
		
		ob_start(); ?>

		<section class="category-hub">
	<!--       <header class="hub-header">
			<h1 class="hub-title"><?php // echo esc_html($parent->name); ?></h1>
			<?php //if ($parent->description): ?>
			  <p class="hub-desc"><?php // echo esc_html($parent->description); ?></p>
			<?php // endif; ?>
		  </header> -->

		  <div class="hub-grid">
			<?php foreach ($subs as $sub): ?>
			  <article class="hub-card">
				<h2 class="hub-card-title"><?php echo esc_html($sub->name); ?></h2>
				<?php if ($sub->description): ?>
				  <p class="hub-card-desc"><?php echo esc_html($sub->description); ?></p>
				<?php endif; ?>

				<?php
				// Derniers articles de la sous-catégorie
				$q = new \WP_Query([
					'post_type'      => 'post',
					'posts_per_page' => (int) $a['posts'],
					'tax_query'      => [[
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => (int) $sub->term_id,
					]],
				]);
				?>

				<?php if ($q->have_posts()): ?>
				  <ul class="hub-posts">
					<?php while ($q->have_posts()): $q->the_post(); ?>
					  <li class="hub-post">
						<a href="<?php the_permalink(); ?>" class="hub-post-link"><?php the_title(); ?></a>
						<p class="hub-post-excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
						 <div class="hub-actions">
							 <!-- Bouton Lire l'article -->
						  <p class="hub-read">
							<a class="hub-read-link" href="<?php the_permalink(); ?>">
							  <!-- icône bulle de chat -->
							  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" class="hub-read-icon" fill="currentColor">
								<path d="M20 2H4a2 2 0 0 0-2 2v14l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
							  </svg>
							  <span><?php _e('Lire l’article', 'masalik'); ?></span>
							</a>
						  </p>
						 <?php
							// ACF: link field (array: ['url','title','target'])
							$book = function_exists('get_field') ? get_field('recommended_book', get_the_ID()) : null;
							if (!empty($book) && is_array($book) && !empty($book['url'])):
							  $book_title = !empty($book['title']) ? $book['title'] : __('Livre recommandé', 'masalik');
							  $book_target = !empty($book['target']) ? $book['target'] : '_blank';
							?>
							  <p class="hub-badge">
								<a class="hub-badge-link" href="<?php echo esc_url($book['url']); ?>"
								   target="<?php echo esc_attr($book_target); ?>" rel="noopener">
								  <!-- petit pictogramme livre -->
								  <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" class="hub-badge-icon">
									<path d="M4 5a2 2 0 0 1 2-2h13v16h-1.5c-1.3 0-2.5.5-3.5 1.3-1-.8-2.2-1.3-3.5-1.3H4V5zm2 0v12h5c1 0 2 .3 3 .8V5H6z" fill="currentColor"/>
								  </svg>
								  <span><?php echo esc_html($book_title); ?></span>
								</a>
							  </p>
							<?php endif; ?>
						  </div>
					  </li>
					<?php endwhile; wp_reset_postdata(); ?>
				  </ul>
				<?php else: ?>
				  <p class="hub-empty">Aucun article pour le moment.</p>
				<?php endif; ?>
			  </article>
			<?php endforeach; ?>
		  </div>
		</section>

		<?php
		return ob_get_clean();
	});
});
