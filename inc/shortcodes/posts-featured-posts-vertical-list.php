<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Shortcode: [posts_featured_posts_vertical_list per_page="10" categories="category" orderby="date" order="DESC"]
 * - Affiche uniquement les posts avec ACF "featured" = true.
 * - Card simple : tag catégorie, titre, excerpt, ACF author/read_time, bouton "Lire plus".
 * - Wrapper flex-col, un article par ligne. Classes: "post".
 */

add_shortcode('posts_featured_posts_vertical_list', function($atts = []) {
    $a = shortcode_atts([
        'per_page'   => 10,
        'categories' => 'category',     // taxo pour le tag d’affichage
        'orderby'    => 'date',         // 'date' | 'title' | ...
        'order'      => 'DESC',         // ASC | DESC
    ], $atts, 'posts_featured_posts_vertical_list');

    $taxonomy = sanitize_key($a['categories']);
    $per_page = max(1, (int)$a['per_page']);
    $orderby  = sanitize_key($a['orderby']);
    $order    = strtoupper($a['order']) === 'ASC' ? 'ASC' : 'DESC';

    // ACF true/false peut être stocké 1/'1'/true → on couvre tout
    $meta_query = [
        'relation' => 'OR',
        [
            'key'     => 'featured',
            'value'   => 1,
            'compare' => '='
        ],
        [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '='
        ],
        [
            'key'     => 'featured',
            'value'   => true,
            'compare' => '='
        ],
    ];

    $q = new WP_Query([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'orderby'        => $orderby,
        'order'          => $order,
        'meta_query'     => $meta_query,
        'no_found_rows'  => true,
    ]);

    if ( ! $q->have_posts() ) {
        return '<div class="masalik-pfpvl-featured-posts masalik-pfpvl-empty">'.esc_html__('Aucun article à la une pour le moment.', 'masalik-pfpvl').'</div>';
    }

    ob_start(); ?>
    <div class="masalik-pfpvl-featured-posts masalik-pfpvl-flex-col">
      <?php while ( $q->have_posts() ) : $q->the_post();
        $pid       = get_the_ID();
        $permalink = get_permalink($pid);
        $title     = get_the_title($pid);
        $excerpt   = wp_strip_all_tags( get_the_excerpt($pid) );

        $terms   = get_the_terms($pid, $taxonomy);
        $primary = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;

        $author   = function_exists('get_field') ? get_field('author', $pid)    : '';
        $readtime = function_exists('get_field') ? get_field('read_time', $pid) : '';
      ?>
        <article class="masalik-pfpvl-card masalik-pfpvl-card--post post shadow-md">
          <div class="masalik-pfpvl-card__body">
            <div class="masalik-pfpvl-card__meta">
              <?php if ($primary): ?>
                <a class="masalik-pfpvl-tag" href="<?php echo esc_url(get_term_link($primary)); ?>">
                  <?php echo esc_html($primary->name); ?>
                  <svg class="masalik-pfpvl-tag__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
              <?php endif; ?>
              <?php if ($author): ?><span class="masalik-pfpvl-meta__item"><?php echo esc_html($author); ?></span><?php endif; ?>
              <?php if ($author && $readtime): ?><span class="masalik-pfpvl-meta__sep">•</span><?php endif; ?>
              <?php if ($readtime): ?><span class="masalik-pfpvl-meta__item"><?php echo esc_html($readtime); ?></span><?php endif; ?>
            </div>

            <h3 class="masalik-pfpvl-card__title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>

            <?php if ($excerpt): ?>
              <p class="masalik-pfpvl-card__excerpt"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
          </div>

          <div class="masalik-pfpvl-card__footer">
            <a class="masalik-pfpvl-btn masalik-pfpvl-btn--read" href="<?php echo esc_url($permalink); ?>">
              <?php esc_html_e('Consulter', 'masalik-pfpvl'); ?>
              <svg class="masalik-pfpvl-btn__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
});
