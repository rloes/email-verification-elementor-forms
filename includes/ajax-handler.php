<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler
{
    public static function send_verification_code()
    {
        check_ajax_referer('send_verification_code', 'security');
        $email = sanitize_email($_POST['email']);
        $code = Code_Generator::generate_code();
        set_transient('verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);

        Email_Handler::send_verification_email($email, $code);

        wp_send_json_success(__('Verification code sent.', 'email-verification-elementor-forms'));
    }
}

