<?php
add_action('wp_enqueue_scripts', function () {
    
    // Ou conditionnel si le shortcode est sur la page
    if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_category_hub' ) ) {
        wp_enqueue_style('articles-category-hub', CHILD_URI . '/assets/css/articles-category-hub.css', [], '1.0');
    }
	
	if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_category_panels' ) ) {
        wp_enqueue_style('articles-category-panels', CHILD_URI . '/assets/css/articles-category-panels.css', [], '1.01');
    }
	
	if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_featured_home' ) ) {
        wp_enqueue_style('articles-featured-home', CHILD_URI . '/assets/css/articles-featured-home.css', [], '1.0');
    }

    if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_mansory' ) ) {
        wp_enqueue_style('articles-mansory', CHILD_URI . '/assets/css/articles-mansory.css', [], '1.22');
    }

    if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_featured_vertical_list' ) ) {
        wp_enqueue_style('articles-featured-vertical-list', CHILD_URI . '/assets/css/articles-featured-vertical-list.css', [], '1.2');
    }

    if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'articles_categories' ) ) {
        wp_enqueue_style('articles-categories', CHILD_URI . '/assets/css/articles-categories.css', [], '1.041');
    }
	
	if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'wc_cards' ) ) {
        wp_enqueue_style('wc-cards', CHILD_URI . '/assets/css/wc-cards.css', [], '1.01');
        wp_enqueue_script('wc-cards', CHILD_URI . '/assets/js/wc-cards.js', [], '1.0', true);
    }
	
	if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'wc_book_of_month' ) ) {
        wp_enqueue_style('wc-book-of-month', CHILD_URI . '/assets/css/wc-book-of-month.css', [], '1.03');
    }
	
	if ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'wc_cats_icons' ) ) {
        wp_enqueue_style('wc-cats-icons', CHILD_URI . '/assets/css/wc-cats-icons.css', [], '1.03');
    }

    // Load custom product layout CSS only on product pages matching /produit/ pattern
    if ( preg_match('#^/produit/#', $_SERVER['REQUEST_URI'] ?? '') ) {
        wp_enqueue_style('custom-product-layout', CHILD_URI . '/assets/css/wc-custom-product-layout.css', [], '1.0');
    }
	
});
