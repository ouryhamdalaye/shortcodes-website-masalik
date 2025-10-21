<?php
// Facultatif mais propre : un namespace pour éviter les collisions
namespace ChildTheme\Shortcodes;

if ( ! function_exists( 'add_shortcode' ) ) {
    return; // sécurité si WP n'a pas fini de charger
}

// On ne déclare le shortcode que si WooCommerce est là
add_action('init', function () {
    add_shortcode('wc_cart_button', function ($atts) { 
        $a = shortcode_atts([
            'title' => 'Panier',
        ], $atts, 'wc_cart_button');

        ob_start(); 
        $cart_count = WC()->cart->cart_contents_count; 
        $cart_url = wc_get_cart_url(); 
        if ( $cart_count < 1 ) {  $hide_btn = 'hide_woo_cart_button'; }
        else { $hide_btn = ''; } ?> 
        
        <a class="menu-item cart-contents <?php echo $hide_btn; ?>" href="<?php echo $cart_url; ?>" title="<?php echo $a['title']; ?>"> 
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
  <path d="M8 3h2L9 8h6l-1-5c3.88 1.75 3.88 1.75 5 4l4 1-3 13H4L1 8l4-1a99.8 99.8 0 0 0 3-4Z"/>
</svg>
            
            <?php 
            if ( $cart_count > 0 ) {?> 
                <span class="cart-contents-count"><?php echo $cart_count; ?></span> 
            <?php } ?> 
        </a>
        <?php echo WC()->cart->get_cart_subtotal(); ?> 
        <?php return ob_get_clean(); 
    });
}); 