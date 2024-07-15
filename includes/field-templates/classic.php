<?php
/** @var \ElementorPro\Modules\Forms\Classes\Form_Base $form */
/** @var int $item_index */

if (!defined('ABSPATH')) exit;
$normal_text = apply_filters('evef/classic/normal_text', __('Send email again', 'email-verification-elementor-forms'));
$success_text = apply_filters('evef/classic/success_text', sprintf(__('Email was sent again, check spam or try again in %s seconds.', 'email-verification-elementor-forms'), "<span class='timer'></span>"));
$error_text = apply_filters('evef/classic/error_text', __('An error occured while sending the mail', 'email-verification-elementor-forms'));
$loader_html = apply_filters('evef/classic/loader_html', '<div class="evef-loader"></div>');
?>

<input <?= $form->get_render_attribute_string('input' . $item_index) ?> >
<span role="button" class="send-code-again" tabindex="0" aria-live="polite">
    <span class="normal"><?= esc_html($normal_text) ?></span>
    <span class="success" style="display:none;" aria-hidden="true"><?= esc_html($success_text) ?></span>
    <span class="error" style="display:none;" aria-hidden="true"><?= esc_html($error_text) ?></span>
    <?= wp_kses_post($loader_html) ?>
</span>
