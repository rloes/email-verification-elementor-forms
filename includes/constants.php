<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Constants{
    const FIELD_TYPE = "evef-verification";
    const CODE_LENGTH = "evef_verification_code_length";
    const EMAIL_FIELD = 'evef_verification_email_field';
    const VERIFICATION_DESIGN = 'evef_verification_design';
    const EMAIL_SUBJECT = 'evef_verification_email_subject';
    const EMAIL_BODY = 'evef_verification_email_body';
    const EMAIL_FROM = 'evef_verification_email_from';
    const EMAIL_FROM_NAME = 'evef_verification_email_from_name';
    const EMAIL_TO_BCC = 'evef_verification_email_to_bcc';
    const AJAX_ACTION_SEND_VERIFICATION_CODE = 'evef_send_verification_code';
    const SCRIPTS_HANDLE = 'evef-scripts';
    const STYLES_HANDLE = 'evef-styles';
    const TEMPLATE_DIR = __DIR__ . "/field-templates/";
}