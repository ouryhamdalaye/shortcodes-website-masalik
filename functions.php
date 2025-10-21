<?php

define( 'CHILD_DIR', get_stylesheet_directory() );
define( 'CHILD_URI', get_stylesheet_directory_uri() );

// Small generic PHP file loader
function child_require( string $rel_path ) {
    $file = trailingslashit( CHILD_DIR ) . ltrim( $rel_path, '/\\' );
    if ( file_exists( $file ) ) { require_once $file; }
}

add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
 
}

function child_theme_slug_setup() {
    load_child_theme_textdomain( 'parent-theme-slug', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'child_theme_slug_setup' );

// Load shortcodes and its assets
child_require('inc/assets.php');
child_require('inc/shortcodes/index.php');

// This filter ensures the cart button count is updated in real time
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_cart_button_count' ); 
function woocommerce_cart_button_count( $fragments ) { 
    ob_start(); 
    $cart_count = WC()->cart->cart_contents_count; 
    $cart_url = wc_get_cart_url(); 
    if ( $cart_count < 1 ) { $hide_btn = 'hide_woo_cart_button'; }
    else { $hide_btn = ''; } ?> 
    <a class="cart-contents menu-item <?php echo $hide_btn; ?>" href="<?php echo $cart_url; ?>" title="<?php _e( 'Consulter votre panier' ); ?>"> 
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M10 19.5c0 .829-.672 1.5-1.5 1.5s-1.5-.671-1.5-1.5c0-.828.672-1.5 1.5-1.5s1.5.672 1.5 1.5zm3.5-1.5c-.828 0-1.5.671-1.5 1.5s.672 1.5 1.5 1.5 1.5-.671 1.5-1.5c0-.828-.672-1.5-1.5-1.5zm1.336-5l1.977-7h-16.813l2.938 7h11.898zm4.969-10l-3.432 12h-12.597l.839 2h13.239l3.474-12h1.929l.743-2h-4.195z"/></svg> 
        <?php if ( $cart_count > 0 ) { ?> 
            <span class="cart-contents-count"><?php echo $cart_count; ?></span> 
        <?php } ?> 
    </a> 
    <?php echo WC()->cart->get_cart_subtotal(); ?> 
    <?php 
    $fragments['a.cart-contents'] = ob_get_clean(); 
    return $fragments; 
}