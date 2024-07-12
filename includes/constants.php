<?php
namespace EVEF;

if (!defined('ABSPATH')) {
    exit;
}

class Constants{
    const FIELD_TYPE = "evef-verification"; // dont change !!!
    const CODE_LENGTH = "evef_verification_code_length"; // dont change !!!
    const EMAIL_FIELD = 'evef_verification_email_field'; // dont change !!!
    const VERIFICATION_DESIGN = 'evef_verification_design'; // dont change !!!
    const VERIFICATION_MESSAGE = 'evef_verification_message'; // dont change !!!
    const EMAIL_SUBJECT = 'evef_verification_email_subject'; // dont change !!!
    const EMAIL_BODY = 'evef_verification_email_body'; // dont change !!!
    const EMAIL_FROM = 'evef_verification_email_from'; // dont change !!!
    const EMAIL_FROM_NAME = 'evef_verification_email_from_name'; // dont change !!!
    const EMAIL_TO_BCC = 'evef_verification_email_to_bcc'; // dont change !!!
    const AJAX_ACTION_SEND_VERIFICATION_CODE = 'evef_send_verification_code';
    const VERIFICATION_CODE_TRANSIENT_PREFIX = 'evef_verification_code_';
    const SCRIPTS_HANDLE = 'evef-scripts';
    const STYLES_HANDLE = 'evef-styles';
    const TEMPLATE_DIR = __DIR__ . "/field-templates/";
}