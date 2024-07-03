<?php
/** @var \ElementorPro\Modules\Forms\Classes\Form_Base $form */
/** @var int $item_index */

if (!defined('ABSPATH')) exit;
?>

<input <?= $form->get_render_attribute_string('input' . $item_index) ?> >
<span role="button" class="send-code-again" tabindex="0">
    <span class="normal"><?= esc_html__('Send email again', 'email-verification-elementor-forms') ?></span>
    <span class="success"><?= esc_html__('Email was sent again', 'email-verification-elementor-forms') ?></span>
    <span class="error"><?= esc_html__('An error occured while sending the mail', 'email-verification-elementor-forms') ?></span>
    <div class="loader"></div>
</span>
