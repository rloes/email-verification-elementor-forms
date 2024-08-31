=== Email Verification for Elementor Forms ===
Contributors: rloes
Tags: email verification, elementor, forms, spam prevention
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add email verification to Elementor forms: users confirm via code, ensuring valid submissions and reducing spam.

== Description ==

Add an email verification field to your Elementor forms. Users receive a code to the email entered on first submit and can only submit the form if they enter the code. This ensures submissions from verified emails, reducing spam.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/email-verification-elementor-forms` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to your Elementor form and add the email verification field.

== Frequently Asked Questions ==

= How does the email verification process work? =

When a user submits the form for the first time, they receive a verification code via the email address they entered. They must enter this code in the form to complete the submission.

= Can I customize the email verification field? =

Yes, the plugin provides filters to customize the text and appearance of the email verification field.

== Screenshots ==

1. **Email Verification Field** - The email verification field as it appears in the form.

== Changelog ==

= 1.1.1 =
* Fix version in main plugin file

= 1.1.0 =
* Optimize error handling and add unslash.
* allow changing width of verification field

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
* First release.

== Filters ==

* `evef/generator/code` - Customize the generated verification code.
* `evef/email/email_to` - Customize the recipient email address.
* `evef/email/email_from` - Customize the email "from" address.
* `evef/email/email_from_name` - Customize the name of the email sender.
* `evef/email/email_to_bcc` - Customize the BCC recipient email address.
* `evef/email/subject` - Customize the email subject.
* `evef/email/body` - Customize the email message content.
* `evef/email/headers` - Customize the email headers.
* `evef/valdation/invalid_code_message` - Customize the error message shown if the code is invalid.
* `evef/classic/normal_text` - Customize the text for resending the email.
* `evef/classic/success_text` - Customize the success message text.
* `evef/classic/error_text` - Customize the error message text.
* `evef/classic/loader_html` - Customize the loader HTML.

== Notes ==

For detailed usage instructions and more customization options, please refer to the documentation included with the plugin.
