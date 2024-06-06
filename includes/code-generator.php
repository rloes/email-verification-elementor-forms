<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Code_Generator
{
    public static function generate_code($digits = 6)
    {
        if ($digits < 1) {
            throw new \InvalidArgumentException(__('Number of digits must be at least 1.', 'email-verification-elementor-forms'));
        }

        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;

        return rand($min, $max);
    }
}


