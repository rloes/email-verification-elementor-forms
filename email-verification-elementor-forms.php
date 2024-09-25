<?php
/**
 * Plugin Name: Email Verification for Elementor Forms
 * Description: Add email verification into your Elementor forms. Users verify their email via a code, ensuring only valid form submissions and reducing spam.
 * Version: 1.1.2
 * Author: Robin - Westsite
 * Author URI: https://westsite-webdesign.de/
 * Plugin URI: https://github.com/rloes/email-verification-elementor-forms
 * Text Domain: email-verification-elementor-forms
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins:  elementor
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
        //TODO: add compatibility check for elementor see: https://developers.elementor.com/docs/addons/compatibility/
        $this->define_constants();
        $this->autoload_classes();
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
        define('EVEF_VERSION', '1.1.2');
        define('EVEF_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('EVEF_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    public function autoload_classes()
    {
        spl_autoload_register(function ($class_name) {
            if (0 === strpos($class_name, __NAMESPACE__ . '\\')) {
                $classes_dir = EVEF_PLUGIN_DIR . 'includes/';
                $class_file = str_replace([__NAMESPACE__ . '\\', '\\', "_"], ['', DIRECTORY_SEPARATOR, "-"], $class_name) . '.php';
                $class_file = strtolower($class_file); // Converts the filename to lowercase

                // Exclude field-templates directory
                if (strpos($class_file, 'field-templates') === false && file_exists($classes_dir . $class_file)) {
                    require $classes_dir . $class_file;
                }
            }
        });
    }

    private function init_hooks()
    {
        add_action('elementor_pro/forms/fields/register', [__NAMESPACE__ . '\Email_Verification_Field', 'register_field']);
        add_action('wp_ajax_' . Constants::AJAX_ACTION_SEND_VERIFICATION_CODE, [__NAMESPACE__ . '\Ajax_Handler', 'send_verification_code']);
        add_action('wp_ajax_nopriv_' . Constants::AJAX_ACTION_SEND_VERIFICATION_CODE, [__NAMESPACE__ . '\Ajax_Handler', 'send_verification_code']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('init', [$this, 'load_textdomain']);
    }

    public function register_assets()
    {
        wp_register_style('evef-styles', EVEF_PLUGIN_URL . 'assets/dist/css/styles.min.css', [], EVEF_VERSION);
        wp_register_script('evef-scripts', EVEF_PLUGIN_URL . 'assets/dist/js/frontend.min.js', ['jquery'], EVEF_VERSION, true);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain("email-verification-elementor-forms", false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

}

// Initialize the plugin only if Elementor Pro is activated
function evef_maybe_initialize_plugin()
{
    if (did_action('elementor/loaded')) {
        if (class_exists('ElementorPro\Plugin')) {
            Plugin::instance();
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning is-dismissible">
                    <p>' . esc_html__('Email Verification for Elementor Forms requires Elementor Pro to be installed and activated.', 'email-verification-elementor-forms') . '</p>
                </div>';
            });
        }
    }
}

add_action('plugins_loaded', 'EVEF\evef_maybe_initialize_plugin', 20);
?>
