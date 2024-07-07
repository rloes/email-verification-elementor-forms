<?php

namespace EVEF;

use function WP_CLI\Utils\args_to_str;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Handler
{
    public static function send_verification_email($email, $code, $email_from = "", $email_from_name = "", $email_to_bcc = false, $subject = '', $body = '')
    {
        $email_from = !empty($email_from) ? $email_from : 'noreply@' . \ElementorPro\Core\Utils::get_site_domain();
        $email_from_name = !empty($email_from_name) ? $email_from_name : get_bloginfo( 'name' );
        $subject = !empty($subject) ? $subject : self::get_default_subject();
        $body = !empty($body) ? $body : self::get_default_body();

        // Apply filters to all parameters
        $email = apply_filters('evef/email/email_to', $email);
        $code = apply_filters('evef/email/code', $code);
        $email_from = apply_filters('evef/email/email_from', $email_from);
        $email_from_name = apply_filters('evef/email/email_from_name', $email_from_name);
        $email_to_bcc = apply_filters('evef/email/email_to_bcc', $email_to_bcc);
        $subject = apply_filters('evef/email/subject', $subject);
        $body = apply_filters('evef/email/body', $body, $code);

        // Replace placeholders with actual values
        $subject = str_replace('{{code}}', $code, $subject);
        $body = str_replace('{{code}}', $code, $body);

        $headers = sprintf('From: %s <%s>' . "\r\n", $email_from_name, $email_from);
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

        if (!empty($email_to_bcc)) {
            $bcc_emails = explode(',', $email_to_bcc);
            foreach ($bcc_emails as $bcc_email) {
                $headers .= 'Bcc: ' . trim($bcc_email) . "\r\n";
            }
        }

        /**
         * Email headers.
         *
         * Filters the headers sent when an email is send from EVEF. This
         * hook allows developers to alter email headers.
         *
         * @param string|array $headers Additional headers.
         * @since 1.0.0
         *
         */
        $headers = apply_filters('evef/email/headers', $headers);

        return wp_mail($email, $subject, $body, $headers);
    }

    public static function get_default_subject()
    {
        return esc_html__('Your Verification Code: {{code}}', 'email-verification-elementor-forms');
    }

    public static function get_default_body()
    {
        return esc_html__('Your Verification Code is: {{code}}', 'email-verification-elementor-forms');
    }
}
