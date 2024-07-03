<?php

namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Handler
{
    public static function send_verification_email($email, $code, $subject = '', $body = '')
    {
        // If subject or body are not provided, use defaults
        $subject = !empty($subject) ? $subject : self::get_default_subject();
        $body = !empty($body) ? $body : self::get_default_body();

        // Replace placeholders with actual values
        $subject = str_replace('{{code}}', $code, $subject);
        $body = str_replace('{{code}}', $code, $body);

        return wp_mail($email, $subject, $body);
    }

    public static function get_default_subject()
    {
        return __('Your Verification Code: {{code}}', 'email-verification-elementor-forms');
    }

    public static function get_default_body()
    {
        return __('Your Verification Code is: {{code}}', 'email-verification-elementor-forms');
    }
}
