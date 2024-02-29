<?php

function chicdressing_enqueue_styles() {
    // Enqueue parent theme styles
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    // Enqueue child theme styles
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css');
}
add_action( 'wp_enqueue_scripts', 'chicdressing_enqueue_styles' );

function chicdressing_dequeue_scripts() {
    // Remove unwanted stylesheets
    wp_dequeue_style( 'ashe-playfair-font');
    wp_dequeue_style( 'ashe-opensans-font');

    // Load Kalam if selected
    if ( ashe_options( 'typography_logo_family' ) == 'Kalam' || ashe_options( 'typography_nav_family' ) == 'Kalam' ) {
        wp_dequeue_style( 'ashe-kalam-font' );
    }

    // Load Rokkitt if selected
    if ( ashe_options( 'typography_logo_family' ) == 'Rokkitt' || ashe_options( 'typography_nav_family' ) == 'Rokkitt' ) {
        wp_dequeue_style( 'ashe-rokkitt-font' );
    }
}
add_action( 'wp_enqueue_scripts', 'chicdressing_dequeue_scripts', 999 ); // Make sure this runs after all styles are enqueued

?>










