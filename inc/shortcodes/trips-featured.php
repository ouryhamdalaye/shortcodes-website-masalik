<?php
namespace ChildTheme\Shortcodes;

if (!defined('ABSPATH')) { exit; }

// Ensure shortcode API is available
if (!function_exists('add_shortcode')) {
    return;
}

/**
 * Safeguard: ensure parent category "Masalik Ar-Rihla" and its three children exist.
 * We do this on init (cheap lookups), only for users who can manage terms, to avoid surprises on public traffic.
 */
// add_action('init', function () {
//     if (!current_user_can('manage_categories')) {
//         return;
//     }

//     $taxonomy = sanitize_key($a['taxonomy']);
//     $parent_slug = 'masalik-ar-rihla';
//     $children = [
//         'voyages'               => 'Voyages',
//         'seminaires'            => 'Séminaires',
//         'retraite-spirituelle'  => 'Retraite Spirituelle',
//     ];

//     // Create/get parent term
//     $parent = get_term_by('slug', $parent_slug, $taxonomy);
//     if (!$parent || is_wp_error($parent)) {
//         $created = wp_insert_term('Masalik Ar-Rihla', $taxonomy, [
//             'slug' => $parent_slug,
//         ]);
//         if (!is_wp_error($created)) {
//             $parent = get_term($created['term_id'], $taxonomy);
//         }
//     }

//     $parent_id = ($parent && !is_wp_error($parent)) ? (int)$parent->term_id : 0;

//     // Create children
//     foreach ($children as $slug => $name) {
//         $exists = get_term_by('slug', $slug, $taxonomy);
//         if (!$exists || is_wp_error($exists)) {
//             wp_insert_term($name, $taxonomy, [
//                 'slug'   => $slug,
//                 'parent' => $parent_id,
//             ]);
//         }
//     }
// });

add_action('init', function () {
    add_shortcode('rihla', function ($atts = []) {
        $a = shortcode_atts([
            'taxonomy'    => 'project_category',
            'parent_slug' => 'masalik-ar-rihla',
            'limit'       => 12,
        ], $atts, 'rihla');

        // Debug helper
        $dbg = function ($step, $data = null) use ($a) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $msg = '';
                if (!is_null($data)) {
                    $msg = is_scalar($data) ? (string)$data : wp_json_encode($data);
                }
                \error_log('[rihla] ' . $step . ' ' . $msg);
            }
        };
        $dbg('start', ['page_id' => get_the_ID(), 'atts' => $a]);

        $taxonomy = taxonomy_exists($a['taxonomy']) ? $a['taxonomy'] : 'category';
        $dbg('using_taxonomy', $taxonomy);
        $parent   = get_term_by('slug', sanitize_title($a['parent_slug']), $taxonomy);
        if (!$parent || is_wp_error($parent)) { $dbg('missing_parent', $a['parent_slug']); return ''; }
        $dbg('parent_term', ['id' => (int)$parent->term_id, 'slug' => $a['parent_slug']]);

        // Build filter chips from child terms of parent
        $child_terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => (int)$parent->term_id,
        ]);
        $chips = [];
        if (!is_wp_error($child_terms)) {
            foreach ($child_terms as $t) {
                $chips[] = ['slug' => $t->slug, 'name' => $t->name];
            }
        }
        $dbg('chips_terms', $chips);

        $query_args = [
            'post_type'           => 'project',
            'post_status'         => 'publish',
            'posts_per_page'      => max(1, (int)$a['limit']),
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
            'tax_query'           => [[
                'taxonomy'         => $taxonomy,
                'field'            => 'term_id',
                'terms'            => [(int)$parent->term_id],
                'include_children' => true,
            ]],
        ];
        $dbg('query_args', $query_args);
        $q = new \WP_Query($query_args);

        if (!$q->have_posts()) { $dbg('no_posts_found'); return ''; }
        $dbg('found_posts', $q->found_posts);
        $dbg('post_ids', wp_list_pluck($q->posts, 'ID'));

        // Build items and assign category slug for filtering
        $items = [];
        while ($q->have_posts()) {
            $q->the_post();
            $post_id = get_the_ID();
            $title   = get_the_title();
            $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $post_id)), 24);
            $img_id  = get_post_thumbnail_id($post_id);
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'medium_large') : '';

            $get_acf = function ($key) use ($post_id) {
                return function_exists('get_field') ? get_field($key, $post_id) : '';
            };

            $subtitle     = (string)($get_acf('subtitle') ?: '');
            $book_name    = (string)($get_acf('book_name') ?: '');
            $author       = (string)($get_acf('author') ?: '');
            $tag          = (string)($get_acf('tag') ?: '');
            $price_tag    = (string)($get_acf('price_tag') ?: '');
            $button_link  = (string)($get_acf('button_link') ?: get_permalink($post_id));
            $accent_color = (string)($get_acf('accent_color') ?: '');

            // Determine the child category slug the post belongs to (direct child of parent)
            $term_slug = '';
            $post_terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'all']);
            if (!is_wp_error($post_terms)) {
                foreach ($post_terms as $t) {
                    if ((int)$t->parent === (int)$parent->term_id) { $term_slug = $t->slug; break; }
                }
                if ($term_slug === '') {
                    foreach ($post_terms as $t) {
                        if ($t->parent) {
                            $anc = get_term($t->parent, $taxonomy);
                            if ($anc && !is_wp_error($anc) && (int)$anc->parent === (int)$parent->term_id) { $term_slug = $anc->slug; break; }
                        }
                    }
                }
            }

            $items[] = [
                'id'           => $post_id,
                'title'        => $title,
                'subtitle'     => $subtitle,
                'excerpt'      => $excerpt,
                'img'          => $img_url,
                'term_slug'    => $term_slug,
                'book_name'    => $book_name,
                'author'       => $author,
                'tag'          => $tag,
                'price_tag'    => $price_tag,
                'button_link'  => $button_link,
                'accent_color' => $accent_color,
            ];
        }
        wp_reset_postdata();

        $dbg('items_count', count($items));

        ob_start();
        ?>
        <section class="mrihla-home-books">
          <div class="mrihla-books-carousel" role="tablist" aria-label="Catégories">
            <button class="mrihla-book-chip is-active" data-filter="*">
              <span class="mrihla-meta">
                <strong><?php echo esc_html__('Toutes les catégories', 'mrihla'); ?></strong>
                <small><?php echo esc_html__('Afficher tout', 'mrihla'); ?></small>
              </span>
            </button>
            <?php foreach ($chips as $chip): ?>
              <button class="mrihla-book-chip" data-filter="[data-cat='<?php echo esc_attr($chip['slug']); ?>']">
                <span class="mrihla-meta">
                  <strong><?php echo esc_html($chip['name']); ?></strong>
                </span>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="mrihla-grid">
            <?php foreach ($items as $it):
              $accent_attr = $it['accent_color'] ? ' style="--accent:'.esc_attr($it['accent_color']).'"' : '';
            ?>
            <article class="mrihla-trip-card" data-cat="<?php echo esc_attr($it['term_slug']); ?>"<?php echo $accent_attr; ?>>
              <div class="mrihla-title">
                <h3><?php echo esc_html($it['title']); ?></h3>
                <?php if ($it['subtitle']): ?><p class="mrihla-muted"><?php echo esc_html($it['subtitle']); ?></p><?php endif; ?>
              </div>
              <div class="mrihla-trip-info">
                <div class="mrihla-book-stack" aria-hidden="true">
                  <span class="mrihla-book mrihla-spine ph"></span>
                  <span class="mrihla-book mrihla-cover<?php echo $it['img'] ? ' has-img' : ''; ?>">
                    <?php if ($it['img']): ?>
                      <img class="mrihla-cover-img" src="<?php echo esc_url($it['img']); ?>" alt="<?php echo esc_attr($it['book_name'] ?: get_the_title($it['id'])); ?>">
                    <?php else: ?>
                      <span class="ph" aria-hidden="true">Couverture</span>
                    <?php endif; ?>

                    <span class="mrihla-tag"><?php echo esc_html__('Autour du livre', 'mrihla'); ?></span>
                  </span>
                </div>

                <div class="mrihla-content">
                  <div class="mrihla-badges">
                    <?php if ($it['tag']): ?><span class="mrihla-pill"><?php echo esc_html($it['tag']); ?></span><?php endif; ?>
                    <?php if ($it['price_tag']): ?><span class="mrihla-pill mrihla-pill--price"><?php echo esc_html($it['price_tag']); ?></span><?php endif; ?>
                  </div>

                  <?php if ($it['excerpt']): ?><p class="mrihla-excerpt"><?php echo esc_html($it['excerpt']); ?></p><?php endif; ?>

                  <div class="mrihla-cta">
                    <?php if ($it['button_link']): ?><a class="mrihla-btn" href="<?php echo esc_url($it['button_link']); ?>"><?php echo esc_html__('Réserver', 'mrihla'); ?></a><?php endif; ?>
                    <a class="mrihla-link" href="<?php echo esc_url(get_permalink($it['id'])); ?>"></a>
                  </div>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
        $out = ob_get_clean();
        $dbg('rendered_output_length', strlen($out));
        return $out;

      });
    });

