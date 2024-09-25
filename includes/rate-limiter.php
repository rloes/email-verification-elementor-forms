<?php

namespace EVEF;

class RateLimiter
{
    private const IP_ATTEMPT_PREFIX = 'evef_verification_attempts_ip_';
    private const EMAIL_ATTEMPT_PREFIX = 'evef_verification_attempts_email_';
    private const IP_TIMEOUT_PREFIX = 'evef_verification_timeout_ip_';
    private const EMAIL_TIMEOUT_PREFIX = 'evef_verification_timeout_email_';

    private const BASE_TIMEOUT = 30; // 30 seconds
    private const GROWTH_FACTOR = 1.25;

    public static function check_rate_limit($email)
    {
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? filter_var(wp_unslash($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP) : false;

        if ($ip_address === false) {
            return [
                "allowed" => false,
                "message" => esc_html__('Invalid IP address or IP address not available.', 'email-verification-elementor-forms'),
                "timeout" => 1
            ];
        }

        $ip_message = esc_html__('You can only request a verification code again after %d seconds for this IP-Address.', 'email-verification-elementor-forms');
        $email_message = esc_html__('You can only request a verification code again after %d seconds for this email.', 'email-verification-elementor-forms');

        $ip_limit = self::check_limit($ip_address, self::IP_ATTEMPT_PREFIX, self::IP_TIMEOUT_PREFIX, $ip_message);
        $email_limit = self::check_limit($email, self::EMAIL_ATTEMPT_PREFIX, self::EMAIL_TIMEOUT_PREFIX, $email_message);

        if ($ip_limit !== true) {
            return $ip_limit;
        }

        if ($email_limit !== true) {
            return $email_limit;
        }

        return true;
    }

    public static function increment_attempt($email)
    {
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? filter_var(wp_unslash($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP) : false;
        if ($ip_address !== false) {
            self::increment($ip_address, self::IP_ATTEMPT_PREFIX, self::IP_TIMEOUT_PREFIX);
            self::increment($email, self::EMAIL_ATTEMPT_PREFIX, self::EMAIL_TIMEOUT_PREFIX);
        }
    }

    private static function check_limit($key, $attempt_prefix, $timeout_prefix, $message=null)
    {
        if(empty($message)){
            $message = esc_html__('You can only request a verification code again after %d seconds.', 'email-verification-elementor-forms');
        }
        $attempt_key = $attempt_prefix . $key;
        $timeout_key = $timeout_prefix . $key;

        $attempts = get_transient($attempt_key) ?: 0;

        if (get_transient($timeout_key)) {
            $remaining_timeout = static::get_transient_remaining_time($timeout_key);
            return [
                'allowed' => false,
                'message' => sprintf(
                    $message,
                    $remaining_timeout
                ),
                'timeout' => $remaining_timeout
            ];
        }

        return true;
    }

    private static function increment($key, $attempt_prefix, $timeout_prefix)
    {
        $attempt_key = $attempt_prefix . $key;
        $timeout_key = $timeout_prefix . $key;

        $attempts = get_transient($attempt_key) ?: 0;
        $attempts++;

        set_transient($attempt_key, $attempts, DAY_IN_SECONDS);

        // Calculate timeout and round down to nearest multiple of 5
        $timeout = self::BASE_TIMEOUT * pow(self::GROWTH_FACTOR, $attempts);
        $rounded_timeout = floor($timeout / 5) * 5;

        set_transient($timeout_key, true, $rounded_timeout);
    }

    /**
     * @param $transient_name
     * @return false|int|mixed|null time until transient expires in seconds
     */
    public static function get_transient_remaining_time($transient_name): int
    {
        // Get the transient timeout (expiration timestamp) from the options table
        $transient_timeout = get_option('_transient_timeout_' . $transient_name);

        if (false === $transient_timeout) {
            // Transient doesn't exist or doesn't have an expiration time
            return 0;
        }

        // Get current timestamp
        $current_time = time();

        // Calculate remaining time
        $remaining_time = (int)round($transient_timeout - $current_time);

        // If the remaining time is less than 0, the transient has expired
        if ($remaining_time <= 0) {
            return 0;
        }

        return $remaining_time; // Return the remaining time in seconds
    }
}