<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Handler
{
    public static function send_verification_email($email, $code)
    {
        $subject = __('Your Verification Code', 'email-verification-elementor-forms');
        $message = __('Your verification code is: ', 'email-verification-elementor-forms') . $code;
        return wp_mail($email, $subject, $message);
    }
}
