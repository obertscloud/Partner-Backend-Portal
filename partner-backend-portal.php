<?php
/*
Plugin Name: Partner Backend Portal
Description: Custom backend interface for partners to manage bookings, commissions, and access.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

define('PBP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PBP_PLUGIN_URL', plugin_dir_url(__FILE__));

// 🔧 Load core plugin logic
require_once PBP_PLUGIN_PATH . 'includes/loader.php';
require_once PBP_PLUGIN_PATH . 'includes/utils.php';
require_once PBP_PLUGIN_PATH . 'includes/class-commission.php';
require_once PBP_PLUGIN_PATH . 'includes/shortcodes.php';
require_once PBP_PLUGIN_PATH . 'includes/admin/admin.php';
require_once PBP_PLUGIN_PATH . 'includes/commission/tiers.php'; // ✅ CORRECTED path

// 🕒 Load admin pages safely after WordPress initializes
add_action('init', function() {
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/dashboard.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/book.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/bookings.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/commissions.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/account.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/assign.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/partner-edit.php';
    require_once PBP_PLUGIN_PATH . 'includes/admin_pages/login.php';
});

// 🔎 AJAX handler for dynamic Select2 search (partner-edit)
add_action('wp_ajax_pp_admin_search_posts', function() {
    check_ajax_referer('pp_admin_search_nonce', 'nonce');

    $term = sanitize_text_field($_POST['term'] ?? '');
    $type = sanitize_text_field($_POST['post_type'] ?? '');
    $results = [];

    if ($term && in_array($type, ['st_tours', 'st_activity'])) {
        $query = new WP_Query([
            'post_type' => $type,
            's' => $term,
            'posts_per_page' => 20
        ]);

        foreach ($query->posts as $post) {
            $lang = get_post_meta($post->ID, 'language', true);
            $results[] = [
                'id'       => $post->ID,
                'label'    => $post->post_title,
                'language' => $lang
            ];
        }
    }

    wp_send_json($results);
});

// ✅ Booking calendar fix for partner role
add_action('init', function() {
    $role     = get_role('partner');
    $customer = get_role('customer');

    if ($role && $customer) {
        foreach ($customer->capabilities as $cap => $grant) {
            $role->add_cap($cap, $grant);
        }
    }

    // Allow partner users to access booking AJAX handlers
    add_action('wp_ajax_tours_add_to_cart', ['STCart', 'tours_add_to_cart']);
    add_action('wp_ajax_activities_add_to_cart', ['STCart', 'activities_add_to_cart']);
    add_action('wp_ajax_nopriv_tours_add_to_cart', ['STCart', 'tours_add_to_cart']);
    add_action('wp_ajax_nopriv_activities_add_to_cart', ['STCart', 'activities_add_to_cart']);
    
    // ✅ Force Traveler theme to recognize 'partner' as a valid booking role
    add_filter('st_booking_user_roles', function($roles) {
        $roles[] = 'partner';
        return array_unique($roles);
    });
});
