<?php
/*
Plugin Name: WP Heatmap & Feedback
Description: Een plugin voor heatmaps, clickmaps en gebruikersfeedback.
Version: 1.2.0
Author: Richard Theuws
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WP_HEATMAP_FEEDBACK_PATH', plugin_dir_path(__FILE__));
define('WP_HEATMAP_FEEDBACK_URL', plugin_dir_url(__FILE__));

require_once WP_HEATMAP_FEEDBACK_PATH . 'includes/class-wp-heatmap-feedback.php';
require_once WP_HEATMAP_FEEDBACK_PATH . 'includes/class-wp-heatmap-feedback-form.php';
require_once WP_HEATMAP_FEEDBACK_PATH . 'includes/class-wp-heatmap-feedback-ajax.php';
require_once WP_HEATMAP_FEEDBACK_PATH . 'includes/class-wp-heatmap-feedback-heatmap.php';

function run_wp_heatmap_feedback() {
    $plugin = new WP_Heatmap_Feedback();
    $plugin->run();
}

run_wp_heatmap_feedback();

register_activation_hook(__FILE__, 'wp_heatmap_feedback_activate');
register_activation_hook(__FILE__, array('WP_Heatmap_Feedback_Form', 'activate'));

function wp_heatmap_feedback_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'heatmap_data';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url varchar(255) NOT NULL,
        x mediumint(9) NOT NULL,
        y mediumint(9) NOT NULL,
        type varchar(20) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}