<?php
/** @var \ElementorPro\Modules\Forms\Classes\Form_Base $form */
/** @var int $item_index */

if (!defined('ABSPATH')) exit;

$normal_text = apply_filters('evef/classic/normal_text', esc_html__('Send email again', 'email-verification-elementor-forms'));
$success_text = apply_filters('evef/classic/success_text', esc_html__('Email was sent again', 'email-verification-elementor-forms'));
$error_text = apply_filters('evef/classic/error_text', esc_html__('An error occured while sending the mail', 'email-verification-elementor-forms'));
$loader_html = apply_filters('evef/classic/loader_html', '<div class="loader"></div>');
?>

<input <?= $form->get_render_attribute_string('input' . $item_index) ?> >
<span role="button" class="send-code-again" tabindex="0">
    <span class="normal"><?= $normal_text ?></span>
    <span class="success elementor-message-success"><?= $success_text ?></span>
    <span class="error elementor-message-danger"><?= $error_text ?></span>
    <?= $loader_html ?>
</span>
