<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

use ElementorPro\Modules\Forms\Fields\Field_Base;

class Verification_Field extends Field_Base
{
    public function get_type()
    {
        return 'verification';
    }

    public function get_name()
    {
        return __('Verification Field', 'email-verification-elementor-forms');
    }

    public function render($item, $item_index, $form)
    {
        $email_field_id = $item['verification_email_field'];
        $form->add_render_attribute(
            'input' . $item_index,
            [
                'type' => 'text',
                'maxlength' => 6,
                'pattern' => '\d{6}',
                'placeholder' => __('Enter 6-digit code', 'email-verification-elementor-forms'),
                'class' => 'elementor-verification-field',
                'data-email-field' => esc_attr($email_field_id),
            ]
        );

        echo '<input ' . $form->get_render_attribute_string('input' . $item_index) . '>';
        ?>
        <style>
            .elementor-field-type-verification:not(.code-sent){
                display:none;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = jQuery('.elementor-verification-field').closest('form');
                var formId = form.find('[name="form_id"]').val()
                var originalSubmitButton = form.find('button[type="submit"]');

                var verificationField = form.find('.elementor-verification-field');
                var verificationFieldGroup = verificationField.closest('.elementor-field-group')

                var emailFieldID = "form-field-" + verificationField.data('email-field');
                var emailField = jQuery('#' + emailFieldID);
                emailField.attr({
                    "required": "required",
                    "aria-required": "true"
                });

                var emailFieldGroup = emailField.closest('.elementor-field-group');
                emailFieldGroup.addClass(["elementor-field-required", "elementor-mark-required"]);

                jQuery(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.data instanceof FormData) {
                        // Directly access the FormData object
                        var formData = settings.data;

                        // Check if form_id is present and matches the form's ID
                        if (formData.has('form_id') && formData.get('form_id') === formId) {
                            verificationFieldGroup.addClass('code-sent')

                        }else{
                            console.log(formData.get('form_id'), form.attr('id'), form)
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function validation($field, $record, $ajax_handler)
    {
        $code_entered = $field['value'];
        $email_field_id = $this->get_verification_email_field_id_from_record($field, $record);
        $email_field = $record->get_field(["id" => $email_field_id])[$email_field_id];
        $email = $email_field['value'];

        if (empty($code_entered)) {
            $code = Code_Generator::generate_code();
            set_transient('verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);

            $mail_sent = Email_Handler::send_verification_email($email, $code);
            if ($mail_sent) {
                $ajax_handler->add_error($field['id'], __('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms'));
                $ajax_handler->add_response_data('ws_elementor_forms_email_verification', [
                    "code_sent" => true,
                    "message" => __('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms')
                ]);
            } else {
                $ajax_handler->add_error_message($ajax_handler->get_default_message($ajax_handler::SERVER_ERROR, $ajax_handler->get_current_form()['settings']));
            }
        } else {
            $code_sent = get_transient('verification_code_' . $email);
            if ($code_entered !== $code_sent) {
                $ajax_handler->add_error($field['id'], __('Invalid verification code.', 'email-verification-elementor-forms'));
            }
        }
    }

    public function get_verification_email_field_id_from_record($field, $record)
    {
        $fields = $record->get("fields");
        $verification_field_index = array_search($field["id"], array_keys($fields));
        $form_field_settings = $record->get("form_settings")["form_fields"];
        $verification_email_field_id = $form_field_settings[$verification_field_index]["verification_email_field"];
        return $verification_email_field_id;
    }

    public static function register_field($form_fields_registrar)
    {
        $form_fields_registrar->register(new self());
    }
}
