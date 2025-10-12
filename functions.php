<?php
// Toujours le chemin du thème enfant
define( 'CHILD_DIR', get_stylesheet_directory() );
define( 'CHILD_URI', get_stylesheet_directory_uri() );

// Petit chargeur générique de fichiers PHP
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