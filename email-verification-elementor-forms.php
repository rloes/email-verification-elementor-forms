<?php
/*
Plugin Name: Email Verification for Elementor Forms
Description: Adds a custom email verification field to Elementor forms.
Version: 1.0
Author: Your Name
Text Domain: email-verification-elementor-forms
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EVEF_VERSION', '1.0');
define('EVEF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVEF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class_name) {
    if (false !== strpos($class_name, 'EVEF\\')) {
        $classes_dir = EVEF_PLUGIN_DIR . 'includes/';
        $class_file = str_replace(['EVEF\\', '\\'], ['', DIRECTORY_SEPARATOR], $class_name) . '.php';
        $class_file = strtolower($class_file); // Converts the filename to lowercase

        if (file_exists($classes_dir . $class_file)) {
            require $classes_dir . $class_file;
        }
    }
});

// Register the custom field
add_action('elementor_pro/forms/fields/register', ['EVEF\\Verification_Field', 'register_field']);

// Handle AJAX requests
add_action('wp_ajax_send_verification_code', ['EVEF\\Ajax_Handler', 'send_verification_code']);
add_action('wp_ajax_nopriv_send_verification_code', ['EVEF\\Ajax_Handler', 'send_verification_code']);

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('evef-styles', EVEF_PLUGIN_URL . 'assets/dist/css/styles.css', [], EVEF_VERSION);
    wp_enqueue_script('evef-scripts', EVEF_PLUGIN_URL . 'assets/dist/js/main.js', ['jquery'], EVEF_VERSION, true);
});
