<?php
/**
 * Plugin Name: Snakes and Ladders Game
 * Description: A WordPress plugin for managing a Snakes and Ladders game.
 * Version: 1.1
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('SNAKES_AND_LADDERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SNAKES_AND_LADDERS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include admin functionality
require_once SNAKES_AND_LADDERS_PLUGIN_DIR . 'includes/admin-functions.php';

// Create database table on plugin activation
register_activation_hook(__FILE__, 'snakes_ladders_create_db');
function snakes_ladders_create_db() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'snakes_ladders_players';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position INT DEFAULT 1
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue CSS styles for the board
add_action('wp_enqueue_scripts', 'snakes_ladders_enqueue_styles');
function snakes_ladders_enqueue_styles() {
    wp_enqueue_style(
        'snakes-ladders-board-styles',
        SNAKES_AND_LADDERS_PLUGIN_URL . 'assets/board-styles.css',
        [],
        '1.1'
    );
}