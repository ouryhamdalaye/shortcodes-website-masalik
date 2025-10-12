<?php
if ( ! defined('ABSPATH') ) { exit; }

if ( ! function_exists('add_shortcode') ) {
    return;
}

add_action('init', function () {
  add_shortcode('articles_mansory', function($atts = []) {
      $a = shortcode_atts([
          'per_page'      => 18,
          'posts_ratio'   => 0.66,
          'categories'    => 'category',   // taxonomie (par défaut: 'category')
          'exclude_cat'   => '',           // slugs (ou ids) séparés par virgule
          'exclude_posts' => '',           // slugs (ou ids) séparés par virgule
      ], $atts, 'articles_mansory');

      $per_page    = max(1, (int)$a['per_page']);
      $posts_ratio = min(1, max(0, (float)$a['posts_ratio']));
      $taxonomy    = sanitize_key($a['categories']);

      $paged = max(1, (int) get_query_var('paged'));
      if ($paged === 1 && isset($_GET['page'])) {
          $paged = max($paged, (int) $_GET['page']);
      }

      // -------- Helpers: convertir slugs -> IDs -------- //
      $parse_list = function(string $csv): array {
          $out = [];
          foreach (explode(',', $csv) as $raw) {
              $raw = trim($raw);
              if ($raw === '') continue;
              $out[] = $raw;
          }
          return $out;
      };

      // Catégories à exclure (slugs -> ids)
      $exclude_cat_raw = $parse_list($a['exclude_cat']);
      $exclude_cat_ids = [];

      if (!empty($exclude_cat_raw)) {
          $slug_list = [];
          foreach ($exclude_cat_raw as $token) {
              if (ctype_digit($token)) {
                  $exclude_cat_ids[] = (int)$token; // compat si ID
              } else {
                  $slug_list[] = sanitize_title($token);
              }
          }
          if (!empty($slug_list)) {
              $term_ids = get_terms([
                  'taxonomy'   => $taxonomy,
                  'slug'       => $slug_list,
                  'hide_empty' => false,
                  'fields'     => 'ids',
              ]);
              if (!is_wp_error($term_ids)) {
                  $exclude_cat_ids = array_merge($exclude_cat_ids, array_map('intval', $term_ids));
              }
          }
          $exclude_cat_ids = array_values(array_unique($exclude_cat_ids));
      }

      // Posts à exclure (slugs -> ids)
      $exclude_posts_raw = $parse_list($a['exclude_posts']);
      $exclude_post_ids  = [];

      if (!empty($exclude_posts_raw)) {
          foreach ($exclude_posts_raw as $token) {
              if (ctype_digit($token)) {
                  $exclude_post_ids[] = (int)$token; // compat si ID
                  continue;
              }
              $slug = sanitize_title($token);
              // get_page_by_path est efficace pour récupérer par slug
              $p = get_page_by_path($slug, OBJECT, 'post');
              if ($p && $p instanceof WP_Post) {
                  $exclude_post_ids[] = (int)$p->ID;
                  continue;
              }
              // fallback: petite requête ciblée par 'name'
              $found = get_posts([
                  'post_type'      => 'post',
                  'name'           => $slug,
                  'post_status'    => 'publish',
                  'posts_per_page' => 1,
                  'fields'         => 'ids',
                  'no_found_rows'  => true,
              ]);
              if (!empty($found)) {
                  $exclude_post_ids[] = (int)$found[0];
              }
          }
          $exclude_post_ids = array_values(array_unique($exclude_post_ids));
      }

      // -------- Répartition -------- //
      $posts_target = (int) round($per_page * $posts_ratio);
      $cats_target  = max(0, $per_page - $posts_target);

      // 1) Articles triés alphabétiquement (exigence)
      $post_query = new \WP_Query([
          'post_type'      => 'post',
          'post_status'    => 'publish',
          'orderby'        => 'title',
          'order'          => 'ASC',
          'posts_per_page' => $posts_target,
          'paged'          => $paged,
          'post__not_in'   => $exclude_post_ids,
      ]);
      $posts = $post_query->posts;

      // 2) Catégories parentes (random stable), exclure via IDs
      $all_parent_terms = get_terms([
          'taxonomy'   => $taxonomy,
          'hide_empty' => true,
          'parent'     => 0,
          'exclude'    => $exclude_cat_ids,
      ]);
      $categories = is_wp_error($all_parent_terms) ? [] : $all_parent_terms;

      if (! empty($categories)) {
          $seed = crc32('masalik_masonry_' . $paged);
          usort($categories, function($a, $b) use ($seed) {
              $ha = crc32($seed . '_' . $a->term_id);
              $hb = crc32($seed . '_' . $b->term_id);
              return $ha <=> $hb;
          });
          $categories = array_slice($categories, 0, $cats_target);
      }

      // 3) Mixer les deux jeux (ordre visuel aléatoire mais stable par page)
      $items = [];
      foreach ($posts as $p)      { $items[] = ['type' => 'post', 'data' => $p]; }
      foreach ($categories as $t) { $items[] = ['type' => 'term', 'data' => $t]; }

      $seed = crc32('masalik_mix_' . $paged);
      usort($items, function($a, $b) use ($seed) {
          $ida = ($a['type'] === 'post') ? $a['data']->ID : ('t' . $a['data']->term_id);
          $idb = ($b['type'] === 'post') ? $b['data']->ID : ('t' . $b['data']->term_id);
          $ha = crc32($seed . '_' . $ida);
          $hb = crc32($seed . '_' . $idb);
          return $ha <=> $hb;
      });

      // Define stable anchor ID
      $anchor_id = 'masalik-masonry-68eaaad4ccfbd';
      
      ob_start(); ?>
      <div class="masalik-featured-wrapper">
        <div class="masalik-masonry" id="<?php echo esc_attr($anchor_id); ?>" data-page="<?php echo esc_attr($paged); ?>">
          <?php
          foreach ($items as $entry) {
              if ($entry['type'] === 'post') {
                  $p = $entry['data']; $pid = $p->ID;
                  $terms = get_the_terms($pid, $taxonomy);
                  $primary = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
                  $author   = function_exists('get_field') ? get_field('author', $pid)    : '';
                  $readtime = function_exists('get_field') ? get_field('read_time', $pid) : '';
                  $permalink = get_permalink($pid); ?>
                  <article class="masalik-card masalik-card--post post">
                    <div class="masalik-card__body">
                      <?php if ($primary): ?>
                        <a class="masalik-tag" href="<?php echo esc_url(get_term_link($primary)); ?>">
                          <?php echo esc_html($primary->name); ?>
                          <svg class="masalik-tag__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                      <?php endif; ?>
                      <h3 class="masalik-card__title">
                        <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($pid)); ?></a>
                      </h3>
                      <p class="masalik-card__excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt($pid))); ?></p>
                      <div class="masalik-card__meta">
                        <?php if ($author): ?><span class="masalik-meta__item"><?php echo esc_html($author); ?></span><?php endif; ?>
                        <?php if ($readtime): ?><span class="masalik-meta__sep">•</span><span class="masalik-meta__item"><?php echo esc_html($readtime); ?></span><?php endif; ?>
                      </div>
                    </div>
                    <div class="masalik-card__footer">
                      <a class="masalik-btn masalik-btn--read" href="<?php echo esc_url($permalink); ?>">
                        <?php esc_html_e('Lire plus', 'masalik'); ?>
                        <svg class="masalik-btn__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      </a>
                    </div>
                  </article>
                  <?php
              } else {
                  $t = $entry['data'];
                  $term_link = get_term_link($t);
                  
          // Récup champ lien_hub (peut être string ou array selon format ACF)
          $raw_hub = function_exists('get_field') ? get_field('lien_hub', $t) : null;
          $hub_url = $term_link; // fallback

          if (is_array($raw_hub) && !empty($raw_hub['url'])) {
            $hub_url = $raw_hub['url'];
          } elseif (is_string($raw_hub) && filter_var($raw_hub, FILTER_VALIDATE_URL)) {
            $hub_url = $raw_hub;
          }
          
          //$term_icon_id 
          $raw = function_exists('get_field') ? get_field('term_icon', 'category_'.$t->term_id) : null;
          $icon_html = '';
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
            $icon_html = sprintf(
              '<span class="masalik-term__icon" style="--icon-url:url(\'%s\')"></span>',
              esc_url($imgUrl)
            );
          } else {
                      $icon_html = '<span class="masalik-term__icon masalik-term__icon--fallback" aria-hidden="true">🏷️</span>';
                  }
          
  //                 if ($term_icon_id) {
  //                     $src = wp_get_attachment_image_url($term_icon_id, 'thumbnail');
  //                     if ($src) { $icon_html = '<img class="masalik-term__icon" src="' . esc_url($src) . '" alt="' . esc_attr($t->name) . '"/>'; }
  //                 }  ?>
                  <section class="masalik-card masalik-card--term category category-<?php echo esc_attr( sanitize_title( $t->slug ) ); ?>">
                    <div class="masalik-card__body">
                      <div class="masalik-term__iconwrap"><?php echo $icon_html; ?></div>
                      <h3 class="masalik-card__title"><?php echo esc_html($t->name); ?></h3>
                      <?php if (!empty($t->description)): ?>
                        <p class="masalik-card__excerpt"><?php echo esc_html(wp_strip_all_tags($t->description)); ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="masalik-card__footer">
                      <a class="masalik-tag masalik-tag--explore" href="<?php echo esc_url($hub_url); ?>">
                        <?php esc_html_e('Explorer', 'masalik'); ?>
                        <svg class="masalik-tag__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      </a>
                    </div>
                  </section>
                  <?php
              }
          } ?>
        </div>

        <?php
        $total_pages = max(1, (int)$post_query->max_num_pages);
        if ($total_pages > 1) :
          $big = 999999999;
          $links = paginate_links([
              'base'         => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
              'format'       => '?paged=%#%',
              'current'      => $paged,
              'total'        => $total_pages,
              'prev_text'    => '«',
              'next_text'    => '»',
              'type'         => 'array',
          ]);
          if (!empty($links)) : ?>
          <nav class="masalik-pagination" aria-label="<?php esc_attr_e('Pagination', 'masalik'); ?>">
            <ul class="masalik-pagination__list">
              <?php foreach ($links as $l) : ?>
                <li class="masalik-pagination__item"><?php echo $l; // phpcs:ignore ?></li>
              <?php endforeach; ?>
            </ul>
          </nav>
          <?php endif;
        endif; ?>
      </div>
      
      <script>
        // Hide #featured on pagination, show on page 1
        (function() {
          const currentPage = <?php echo esc_js($paged); ?>;
          const featuredElement = document.getElementById('featured');
          
          if (featuredElement) {
            if (currentPage === 1) {
              featuredElement.style.display = 'flex';
            } else {
              featuredElement.style.display = 'none';
            }
          }
          
          // Handle pagination clicks
          document.addEventListener('click', function(e) {
            if (e.target.matches('.masalik-pagination__item a')) {
              const href = e.target.getAttribute('href');
              const isPage1 = href.includes('page/1') || !href.includes('page/');
              
              if (featuredElement) {
                if (isPage1) {
                  featuredElement.style.display = 'flex';
                } else {
                  featuredElement.style.display = 'none';
                }
              }
            }
          });
        })();
      </script>
      <?php

      wp_reset_postdata();
      return ob_get_clean();
  });
});