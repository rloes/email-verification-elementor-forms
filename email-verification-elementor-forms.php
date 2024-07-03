<?php
/*
Plugin Name: Email Verification for Elementor Forms
Description: Adds a custom email verification field to Elementor forms.
Version: 1.0
Author: Your Name
Text Domain: email-verification-elementor-forms
Domain Path: /languages
*/

namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    private static $instance = null;

    private function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function define_constants()
    {
        define('EVEF_VERSION', '1.0');
        define('EVEF_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('EVEF_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    private function includes()
    {
        $require = [
            "ajax-handler",
            "code-generator",
            "email-handler",
            "email-verification-field"
        ];
        foreach ($require as $file) {
            require_once EVEF_PLUGIN_DIR . 'includes/' . $file . '.php';
        }
    }

    public function autoload_classes($class_name)
    {
        if (false !== strpos($class_name, __NAMESPACE__ . '\\')) {
            $classes_dir = EVEF_PLUGIN_DIR . 'includes/';
            $class_file = str_replace([__NAMESPACE__ . '\\', '\\'], ['', DIRECTORY_SEPARATOR], $class_name) . '.php';
            $class_file = strtolower($class_file); // Converts the filename to lowercase

            if (file_exists($classes_dir . $class_file)) {
                require $classes_dir . $class_file;
            }
        }
    }

    private function init_hooks()
    {
        add_action('elementor_pro/forms/fields/register', [__NAMESPACE__ . '\Email_Verification_Field', 'register_field']);
        add_action('wp_ajax_evef_send_verification_code', [__NAMESPACE__ . '\Ajax_Handler', 'send_verification_code']);
        add_action('wp_ajax_nopriv_evef_send_verification_code', [__NAMESPACE__ . '\Ajax_Handler', 'send_verification_code']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('elementor-pro/forms/pre_render', function($instance, $form){
            wp_enqueue_style( 'elementor-icons' );
        }, 10, 2);
    }

    public function register_assets()
    {
        wp_register_style('evef-styles', EVEF_PLUGIN_URL . 'assets/dist/css/styles.css', [], EVEF_VERSION);
        wp_register_script('evef-scripts', EVEF_PLUGIN_URL . 'assets/dist/js/main.js', ['jquery'], EVEF_VERSION, true);
    }

}

// Initialize the plugin only if Elementor Pro is activated
function evef_maybe_initialize_plugin() {
    if (did_action('elementor/loaded')) {
        if (class_exists('ElementorPro\Plugin')) {
            Plugin::instance();
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">
                    <p>' . __('Email Verification for Elementor Forms requires Elementor Pro to be installed and activated.', 'email-verification-elementor-forms') . '</p>
                </div>';
            });
        }
    }
}

add_action('plugins_loaded', 'EVEF\evef_maybe_initialize_plugin', 20);
?>
