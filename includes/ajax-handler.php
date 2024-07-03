<?php

namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler
{
    public static function send_verification_code()
    {
        check_ajax_referer('evef_send_verification_code');

        $email = sanitize_email($_POST['email']);
        $code_length = intval($_POST['code_length'] ?? 0);
        $code_length = $code_length > 0 ? $code_length : 6;
        $code = Code_Generator::generate_code($code_length);
        set_transient('evef_verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);

        // Retrieve page ID and widget ID from AJAX request
        $post_id = intval($_POST['post_id']);
        $widget_id = sanitize_text_field($_POST['widget_id']);
        $field_id = sanitize_key($_POST["field_id"]);

        // Get widget settings
        $settings = self::get_widget_settings($post_id, $widget_id);
        if ($settings) {
            $form_fields = $settings["form_fields"];
            $field = static::searchInArray($form_fields, "custom_id", $field_id);
            ["code_length" => $code_length] = $field;
        }


        Email_Handler::send_verification_email($email, $code);

        // Send response with widget settings
        wp_send_json_success([
            'message' => __('Verification code sent.', 'email-verification-elementor-forms'),
            'widget_settings' => $settings
        ]);
    }

    /**
     * Retrieve the settings of a widget by post ID and widget ID.
     *
     * @param int $post_id The post ID.
     * @param string $widget_id The widget ID.
     * @return array|null The widget settings or null if not found.
     */
    private static function get_widget_settings($post_id, $widget_id)
    {
        $document = \ElementorPro\Plugin::elementor()->documents->get($post_id);

        if (!$document) {
            return null;
        }

        $element_data = $document->get_elements_data();
        $widget_data = \Elementor\Utils::find_element_recursive($element_data, $widget_id);

        if (!$widget_data || !isset($widget_data['settings'])) {
            return null;
        }

        return $widget_data['settings'];
    }

    public static function searchInArray($array, $key, $value)
    {
        $filteredArray = array_filter($array, function ($subArray) use ($key, $value) {
            return isset($subArray[$key]) && $subArray[$key] == $value;
        });

        return !empty($filteredArray) ? array_shift($filteredArray) : null;
    }
}

