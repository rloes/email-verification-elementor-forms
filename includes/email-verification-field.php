<?php

namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Verification_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base
{

    public function __construct()
    {
        parent::__construct();
        add_filter("elementor_pro/forms/render/item/".Constants::FIELD_TYPE, [$this, "add_design_class_to_field_group"], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'localize_scripts']);

    }

    public function get_type()
    {
        return Constants::FIELD_TYPE;
    }

    public function get_name()
    {
        return __('Verification Field', 'email-verification-elementor-forms');
    }

    public $depended_scripts = [ 'evef-scripts' ];

    public $depended_styles = [ 'evef-styles' ];

    public function render($item, $item_index, $form)
    {
        $email_field_id = $item['verification_email_field'];
        $design = isset($item['verification_design']) ? $item['verification_design'] : 'classic';
        $code_length = isset($item[Constants::CODE_LENGTH]) ? (int)$item[Constants::CODE_LENGTH] : 6;

        $form->add_render_attribute(
            'input' . $item_index,
            [
                'type' => 'text',
                'maxlength' => $code_length,
                'pattern' => '\d{' . $code_length . '}',
                'placeholder' => sprintf(esc_attr__('Enter %s-digit code', 'email-verification-elementor-forms'), $code_length),
                'class' => 'elementor-verification-field elementor-field-textual',
                'data-email-field' => esc_attr($email_field_id),
            ]
        );

        $template_file = __DIR__ . "/field-templates/{$design}.php";

        if (file_exists($template_file)) {
            /**
             * @var \ElementorPro\Modules\Forms\Classes\Form_Record $form
             * @var int $item_index
             * @var int $code_length
             */
            include $template_file;
        } else {
            echo "<p>" . __('Template not found.', 'email-verification-elementor-forms') . "</p>";
        }
    }


    public function validation($field, $record, $ajax_handler)
    {
        $code_entered = $field['value'];
        $email_field_id = $this->get_setting_for_field_from_record($record, $field, "verification_email_field");
        $email_field = $record->get_field(["id" => $email_field_id])[$email_field_id];
        $email = $email_field['value'];

        if (empty($code_entered)) {
            $subject = $this->get_setting_for_field_from_record($record, $field, "verification_email_subject");
            $body = $this->get_setting_for_field_from_record($record, $field, "verification_email_body");
            $code_length = $this->get_setting_for_field_from_record($record, $field, Constants::CODE_LENGTH);
            $code = Code_Generator::generate_code($code_length);
            set_transient('evef_verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);

            $mail_sent = Email_Handler::send_verification_email($email, $code, $subject, $body);
            if ($mail_sent) {
                $ajax_handler->add_error($field['id'], __('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms'));
                $ajax_handler->add_response_data($field['id'], [
                    "code_sent" => true,
                    "message" => __('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms')
                ]);
            } else {
                $ajax_handler->add_error_message($ajax_handler->get_default_message($ajax_handler::SERVER_ERROR, $ajax_handler->get_current_form()['settings']));
            }
        } else {
            $code_sent = get_transient('evef_verification_code_' . $email);
            if ($code_entered !== $code_sent) {
                $ajax_handler->add_error($field['id'], __('Invalid verification code.', 'email-verification-elementor-forms'));
            }
        }
    }


    public function get_setting_for_field_from_record($record, $field, $setting)
    {
        $fields = $record->get("fields");
        $verification_field_index = array_search($field["id"], array_keys($fields));
        $form_field_settings = $record->get("form_settings")["form_fields"];
        if (isset($form_field_settings[$verification_field_index][$setting])) {
            return $form_field_settings[$verification_field_index][$setting];
        }
        return null;
    }

    public static function register_field($form_fields_registrar)
    {
        $form_fields_registrar->register(new self());
    }

    public function update_controls($widget)
    {

        $elementor = \ElementorPro\Plugin::elementor();

        $control_data = $elementor->controls_manager->get_control_from_stack($widget->get_unique_name(), 'form_fields');

        if (is_wp_error($control_data)) {
            return;
        }

        $fields = $control_data['fields'];
        $field_controls = [
            'verification_email_field' => [
                'name' => 'verification_email_field',
                'label' => esc_html__('E-Mail Field', 'textdomain'),
                'type' => \Elementor\Controls_Manager::TEXT,
                "default" => "email",
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
                'ai' => false,
            ],
            'verification_design' => [
                'name' => 'verification_design',
                'label' => esc_html__('Verification Design', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'classic' => __('Classic', 'email-verification-elementor-forms'),
                    'separate_inputs' => __('Separate Inputs', 'email-verification-elementor-forms'),
                ],
                'default' => 'classic',
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            Constants::CODE_LENGTH => [
                'name' => Constants::CODE_LENGTH,
                'label' => esc_html__('Verification Code Length', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            'verification_email_subject' => [
                'name' => 'verification_email_subject',
                'label' => esc_html__('Verification Email Subject', 'email-verification-elementor-forms'),
                'label_block' => true,
                'description' => esc_html__('You can use {{code}}, it will be replaced with the verification code.', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => Email_Handler::get_default_subject(),
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            'verification_email_body' => [
                'name' => 'verification_email_body',
                'label' => esc_html__('Verification Email Body', 'email-verification-elementor-forms'),
                'description' => esc_html__('You can use {{code}}, it will be replaced with the verification code.', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => Email_Handler::get_default_body(),
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
        ];

        $control_data['fields'] = $this->inject_field_controls($control_data['fields'], $field_controls);
        foreach ($control_data['fields'] as $index => $field) {
            if ('required' === $field['name'] || 'width' === $field['name']) {
                $control_data['fields'][$index]['conditions']['terms'][] = [
                    'name' => 'field_type',
                    'operator' => '!in',
                    'value' => [
                        'verification',
                    ],
                ];
            }
        }

        $widget->update_control('form_fields', $control_data);

    }

    public function add_design_class_to_field_group($item, $item_index, $form)
    {
        $design = $item["verification_design"];
        $form->add_render_attribute(
            'field-group' . $item_index, [
            "class" => "verification-field-{$design}"
        ]);

        return $item;
    }

    public function localize_scripts()
    {
        // Localize the script with new data
        wp_localize_script('evef-scripts', 'verificationFieldHandlerVars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evef_send_verification_code')
        ));
    }
}
