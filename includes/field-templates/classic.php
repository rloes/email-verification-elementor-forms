<?php
/** @var \ElementorPro\Modules\Forms\Classes\Form_Base $form */
/** @var int $item_index */

if (!defined('ABSPATH')) exit;
$normal_text = apply_filters('evef/classic/normal_text', __('Send email again', 'email-verification-elementor-forms'));
/* translators: %s: Countdown, until sent again is available again. */
$success_text = apply_filters('evef/classic/success_text', __('Email was sent again, check spam or try again in %s seconds.', 'email-verification-elementor-forms'));
$error_text = apply_filters('evef/classic/error_text', __('An error occured while sending the mail', 'email-verification-elementor-forms'));
$loader_html = apply_filters('evef/classic/loader_html', '<div class="evef-loader"></div>');
?>

<input <?php echo $form->get_render_attribute_string('input' . $item_index) ?> >
<span role="button" class="send-code-again" tabindex="0" aria-live="polite">
    <span class="normal"><?php echo esc_html($normal_text) ?></span>
    <span class="success" style="display:none;" aria-hidden="true"><?php echo sprintf(esc_html($success_text),"<span class='timer'></span>") ?></span>
    <span class="error" style="display:none;" aria-hidden="true"><?php echo esc_html($error_text) ?></span>
    <?php echo wp_kses_post($loader_html) ?>
</span>
