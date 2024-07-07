<?php

namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler
{
    public static function send_verification_code()
    {
        check_ajax_referer(Constants::AJAX_ACTION_SEND_VERIFICATION_CODE);

        $ip_address = $_SERVER['REMOTE_ADDR'];

        if (get_transient('evef_verification_timeout_ip_' . $ip_address)) {
            wp_send_json_error([
                'message' => esc_html__('You can only request a verification code once every 30 seconds from this IP address.', 'email-verification-elementor-forms')
            ]);
            return;
        }

        $email = sanitize_email($_POST['email']);

        // Retrieve page ID and widget ID from AJAX request
        $post_id = intval($_POST['post_id']);
        $widget_id = sanitize_text_field($_POST['widget_id']);
        $field_id = sanitize_key($_POST["field_id"]);

        // Get widget settings
        $settings = self::get_widget_settings($post_id, $widget_id);
        if (!empty($settings)) {
            $form_fields = $settings["form_fields"];
            $field_index = array_search($field_id, array_column($form_fields, "custom_id"));
            if ($field_index) {
                $field = $form_fields[$field_index];
                // get all relevant settings from field settings by deconstructing
                [
                    Constants::CODE_LENGTH => $code_length,
                    Constants::EMAIL_FROM => $email_from,
                    Constants::EMAIL_FROM_NAME => $email_from_name,
                    Constants::EMAIL_TO_BCC => $email_to_bcc,
                    Constants::EMAIL_SUBJECT => $subject,
                    Constants::EMAIL_BODY => $body,
                ] = $field;
            }

        }
        $code_length = intval($code_length);
        $code_length = $code_length > 0 ? $code_length : 6;
        $code = Code_Generator::generate_code($code_length);
        set_transient('evef_verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);


        Email_Handler::send_verification_email($email, $code,$email_from, $email_from_name, $email_to_bcc, $subject, $body);

        set_transient('evef_verification_timeout_ip_' . $ip_address, true, 30);
        // Send response with widget settings
        wp_send_json_success([
            'message' => esc_html__('Verification code sent.', 'email-verification-elementor-forms'),
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
}

