<?php
namespace ChildTheme\Shortcodes;

if ( ! defined('ABSPATH') ) { exit; }

if ( ! function_exists('add_shortcode') ) {
    return;
}

add_action('init', function () {
  add_shortcode('articles_categories', function($atts = []) {
      $a = shortcode_atts([
          'taxonomy'    => 'category',   // taxonomie (par défaut: 'category')
          'exclude_cat' => '',           // slugs (ou ids) séparés par virgule
          'limit'       => 10,           // nombre max de catégories à afficher
      ], $atts, 'articles_categories');

      $taxonomy = sanitize_key($a['taxonomy']);
      $limit = max(1, (int)$a['limit']);

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

      // Récupérer les catégories parentes
      $categories = get_terms([
          'taxonomy'   => $taxonomy,
          'hide_empty' => true,
          'parent'     => 0,
          'exclude'    => $exclude_cat_ids,
          'number'     => $limit,
      ]);

      if (is_wp_error($categories) || empty($categories)) {
          return '';
      }

      ob_start(); ?>
      <div class="masalik-categories-horizontal">
        <ul class="masalik-categories-list">
          <?php foreach ($categories as $term): 
            // Récup champ lien_hub (peut être string ou array selon format ACF)
            $raw_hub = function_exists('get_field') ? get_field('lien_hub', $term) : null;
            $hub_url = get_term_link($term); // fallback

            if (is_array($raw_hub) && !empty($raw_hub['url'])) {
              $hub_url = $raw_hub['url'];
            } elseif (is_string($raw_hub) && filter_var($raw_hub, FILTER_VALIDATE_URL)) {
              $hub_url = $raw_hub;
            }
            
            // Récupérer l'icône du terme
            $raw_icon = function_exists('get_field') ? get_field('term_icon', 'category_'.$term->term_id) : null;
            $icon_html = '';
            $imgUrl = '';

            if (is_array($raw_icon)) {
              // Return format = Array (ACF peut donner 'ID' ou 'id' + 'url')
              if (!empty($raw_icon['url']))  $imgUrl = $raw_icon['url'];
            } else if (is_string($raw_icon) && filter_var($raw_icon, FILTER_VALIDATE_URL)) {
              $imgUrl = $raw_icon;                                // Return format = URL
            } else {
              $imgUrl = '';
            }

            if ($imgUrl) {
              $icon_html = sprintf(
                '<span class="masalik-category-icon" style="--icon-url:url(\'%s\')"></span>',
                esc_url($imgUrl)
              );
            } else {
              $icon_html = '<span class="masalik-category-icon masalik-category-icon--fallback" aria-hidden="true">🏷️</span>';
            }
          ?>
            <li class="masalik-category-item">
              <a href="<?php echo esc_url($hub_url); ?>" class="masalik-category-link">
                <?php echo $icon_html; ?>
                <span class="masalik-category-name"><?php echo esc_html($term->name); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php

      return ob_get_clean();
  });
});
