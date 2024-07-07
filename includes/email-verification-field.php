<?php

namespace EVEF;

use function cli\err;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Verification_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base
{

    public function __construct()
    {
        parent::__construct();
        add_filter("elementor_pro/forms/render/item/" . Constants::FIELD_TYPE, [$this, "add_design_class_to_field_group"], 10, 3);
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

    public $depended_scripts = [Constants::SCRIPTS_HANDLE];

    public $depended_styles = [Constants::STYLES_HANDLE];

    public function render($item, $item_index, $form)
    {
        $email_field_id = $item[Constants::EMAIL_FIELD];
        $design = $item[Constants::VERIFICATION_DESIGN] ?? 'classic';
        $code_length = isset($item[Constants::CODE_LENGTH]) ? (int)$item[Constants::CODE_LENGTH] : 6;

        $form->add_render_attribute(
            'input' . $item_index,
            [
                'maxlength' => $code_length,
                'pattern' => '\d{' . $code_length . '}',
                'placeholder' => sprintf(esc_attr__('Enter %s-digit code', 'email-verification-elementor-forms'), $code_length),
                'class' => 'elementor-'. $this->get_type() .'-field elementor-field-textual',
                'data-email-field' => esc_attr($email_field_id),
            ]
        );
        $form->add_render_attribute(
            'input' . $item_index, "type", 'text', true
        );

        $template_file = Constants::TEMPLATE_DIR . "{$design}.php";

        if (file_exists($template_file)) {
            /**
             * @var \ElementorPro\Modules\Forms\Classes\Form_Record $form
             * @var int $item_index
             * @var int $code_length
             */
            include $template_file;
        } else {
            echo "<p>" . esc_html__('Template not found.', 'email-verification-elementor-forms') . "</p>";
        }
    }

    public function validation($field, $record, $ajax_handler)
    {
        $code_entered = $field['value'];
        $email_field_id = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_FIELD);
        $email_field = $record->get_field(["id" => $email_field_id])[$email_field_id];
        $email = $email_field['value'];

        if (empty($code_entered)) {
            $email_from = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_FROM);
            $email_from_name = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_FROM_NAME);
            $email_to_bcc = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_TO_BCC);
            $subject = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_SUBJECT);
            $body = $this->get_setting_for_field_from_record($record, $field, Constants::EMAIL_BODY);
            $code_length = $this->get_setting_for_field_from_record($record, $field, Constants::CODE_LENGTH);
            error_log($code_length);
            $code = Code_Generator::generate_code($code_length);
            set_transient('evef_verification_code_' . $email, $code, 15 * MINUTE_IN_SECONDS);

            $mail_sent = Email_Handler::send_verification_email($email, $code, $email_from, $email_from_name, $email_to_bcc, $subject, $body);
            if ($mail_sent) {
                $ajax_handler->add_error($field['id'], esc_html__('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms'));
                $ajax_handler->add_response_data($field['id'], [
                    "code_sent" => true,
                    "message" => esc_html__('We have sent a verification code to your email address. Please enter it here and submit the form again.', 'email-verification-elementor-forms')
                ]);
            } else {
                $ajax_handler->add_error_message($ajax_handler->get_default_message($ajax_handler::SERVER_ERROR, $ajax_handler->get_current_form()['settings']));
            }
        } else {
            $code_sent = get_transient('evef_verification_code_' . $email);
            if ($code_entered !== $code_sent) {
                $ajax_handler->add_error($field['id'], esc_html__('Invalid verification code.', 'email-verification-elementor-forms'));
            }
        }
    }

    public function get_setting_for_field_from_record($record, $field, $setting)
    {
        $form_field_settings = $record->get("form_settings")["form_fields"];
        $verification_field_index = array_search($field["id"], array_column($form_field_settings, "custom_id"));
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
            Constants::EMAIL_FIELD => [
                'name' => Constants::EMAIL_FIELD,
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
            Constants::VERIFICATION_DESIGN => [
                'name' => Constants::VERIFICATION_DESIGN,
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
                'dynamic' => [
                    'active' => true,
                ],
            ],
            Constants::EMAIL_FROM => [
                'name' => Constants::EMAIL_FROM,
                'label' => esc_html__('From Email', 'elementor-pro'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'noreply@' . \ElementorPro\Core\Utils::get_site_domain(),
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            Constants::EMAIL_FROM_NAME => [
                'name' => Constants::EMAIL_FROM_NAME,
                'label' => esc_html__('From Name', 'elementor-pro'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => get_bloginfo('name'),
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            Constants::EMAIL_TO_BCC => [
                'name' => Constants::EMAIL_TO_BCC,
                'label' => esc_html__('Bcc', 'elementor-pro'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'title' => esc_html__('Separate emails with commas', 'elementor-pro'),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
            ],
            Constants::EMAIL_SUBJECT => [
                'name' => Constants::EMAIL_SUBJECT,
                'label' => esc_html__('Verification Email Subject', 'email-verification-elementor-forms'),
                'label_block' => true,
                'description' => esc_html__('You can use {{code}}, it will be replaced with the verification code.', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => Email_Handler::get_default_subject(),
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'ai' => [
                    'active' => false,
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
                'dynamic' => [
                    'active' => true,
                ],
            ],
            Constants::EMAIL_BODY => [
                'name' => Constants::EMAIL_BODY,
                'label' => esc_html__('Verification Email Body', 'email-verification-elementor-forms'),
                'description' => esc_html__('You can use {{code}}, it will be replaced with the verification code.', 'email-verification-elementor-forms'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => Email_Handler::get_default_body(),
                'condition' => [
                    'field_type' => $this->get_type(),
                ],
                'ai' => [
                    'active' => false,
                ],
                'tab' => 'content',
                'inner_tab' => 'form_fields_content_tab',
                'tabs_wrapper' => 'form_fields_tabs',
                'dynamic' => [
                    'active' => true,
                ],
            ],
        ];

        $control_data['fields'] = $this->inject_field_controls($control_data['fields'], $field_controls);
        foreach ($control_data['fields'] as $index => $field) {
            if ('required' === $field['name'] || 'width' === $field['name']) {
                $control_data['fields'][$index]['conditions']['terms'][] = [
                    'name' => 'field_type',
                    'operator' => '!in',
                    'value' => [
                        Constants::FIELD_TYPE,
                    ],
                ];
            }
        }

        $widget->update_control('form_fields', $control_data);
    }

    public function add_design_class_to_field_group($item, $item_index, $form)
    {
        $design = $item[Constants::VERIFICATION_DESIGN];
        $form->add_render_attribute(
            'field-group' . $item_index, [
            "class" => "verification-field-{$design}"
        ]);

        return $item;
    }

    public function localize_scripts()
    {
        // Localize the script with new data
        wp_localize_script(Constants::SCRIPTS_HANDLE, 'verificationFieldHandlerVars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(Constants::AJAX_ACTION_SEND_VERIFICATION_CODE)
        ));
    }
}
