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

        // Validate and sanitize email
        if (empty($_POST['email'])) {
            wp_send_json_error([
                'message' => esc_html__('Email is required.', 'email-verification-elementor-forms')
            ]);
            return;
        }
        $email = sanitize_email(wp_unslash($_POST['email']));

        $rate_limit_check = Rate_Limiter::check_rate_limit($email);
        if ($rate_limit_check !== true) {
            wp_send_json_error([
                'message' => $rate_limit_check['message'],
                'timeout' => $rate_limit_check['timeout']
            ]);
            return;
        }

        // Validate and sanitize post_id
        if (empty($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error([
                'message' => esc_html__('Invalid or missing post ID.', 'email-verification-elementor-forms')
            ]);
            return;
        }
        $post_id = intval($_POST['post_id']);

        // Validate and sanitize widget_id
        if (empty($_POST['widget_id'])) {
            wp_send_json_error([
                'message' => esc_html__('Widget ID is required.', 'email-verification-elementor-forms')
            ]);
            return;
        }
        $widget_id = sanitize_key($_POST['widget_id']);

        // Validate and sanitize field_id
        if (empty($_POST['field_id'])) {
            wp_send_json_error([
                'message' => esc_html__('Field ID is required.', 'email-verification-elementor-forms')
            ]);
            return;
        }
        $field_id = sanitize_key($_POST["field_id"]);

        // Get widget settings
        $settings = self::get_widget_settings($post_id, $widget_id);
        if (empty($settings)) {
            wp_send_json_error([
                'message' => esc_html__('Form Widget settings could not be retrieved.', 'email-verification-elementor-forms')
            ]);
            return;
        }

        $form_fields = $settings["form_fields"];
        $field_index = array_search($field_id, array_column($form_fields, "custom_id"));
        if ($field_index === false) {
            wp_send_json_error([
                'message' => esc_html__('Field ID not found in widget settings.', 'email-verification-elementor-forms')
            ]);
            return;
        }

        $field = $form_fields[$field_index];
        $field = array_merge([
            Constants::EMAIL_TO_BCC => null, // Default value if not present
        ], $field);
        [
            Constants::CODE_LENGTH => $code_length,
            Constants::EMAIL_FROM => $email_from,
            Constants::EMAIL_FROM_NAME => $email_from_name,
            Constants::EMAIL_TO_BCC => $email_to_bcc,
            Constants::EMAIL_SUBJECT => $subject,
            Constants::EMAIL_BODY => $body,
        ] = $field;

        $code_length = intval($code_length);
        $code_length = $code_length > 0 ? $code_length : 6;
        $code = Code_Generator::generate_code($code_length);
        set_transient(Constants::VERIFICATION_CODE_TRANSIENT_PREFIX . $email, $code, 15 * MINUTE_IN_SECONDS);

        $mail_sent = Email_Handler::send_verification_email($email, $code, $email_from, $email_from_name, $email_to_bcc, $subject, $body);
        if ($mail_sent) {
            $timeout = Rate_Limiter::increment_attempt($email);
            wp_send_json_success([
                'message' => esc_html__('Verification code sent.', 'email-verification-elementor-forms'),
                'timeout' => $timeout
            ]);
        } else {
            wp_send_json_error([
                'message' => esc_html__('The mail could not be sent. Please contact an admin.', 'email-verification-elementor-forms')
            ]);
        }
        return;
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


