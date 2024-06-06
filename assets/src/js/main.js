document.addEventListener('DOMContentLoaded', function () {
    const verificationFields = document.querySelectorAll('form .elementor-verification-field')
    verificationFields.forEach((verificationField) => {
        const form = jQuery(verificationField).closest('form');
        const formId = form.find('[name="form_id"]').val()
        const originalSubmitButton = form.find('button[type="submit"]');

        const verificationFieldGroup = verificationField.closest('.elementor-field-group')

        const emailFieldID = "form-field-" + verificationField.data('email-field');
        const emailField = jQuery('#' + emailFieldID);
        emailField.attr({
            "required": "required",
            "aria-required": "true"
        });

        const emailFieldGroup = emailField.closest('.elementor-field-group');
        emailFieldGroup.addClass(["elementor-field-required", "elementor-mark-required"]);

        jQuery(document).ajaxComplete(function (event, xhr, settings) {
            if (settings.data instanceof FormData) {
                // Directly access the FormData object
                const formData = settings.data;

                // Check if form_id is present and matches the form's ID
                if (formData.has('form_id') && formData.get('form_id') === formId) {
                    verificationFieldGroup.addClass('code-sent')

                } else {
                    console.log(formData.get('form_id'), form.attr('id'), form)
                }
            }
        });
    })
});