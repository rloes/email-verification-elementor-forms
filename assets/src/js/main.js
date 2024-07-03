document.addEventListener('DOMContentLoaded', () => {
    class VerificationFieldHandler {
        constructor(field) {
            this.field = field;
            this.ajaxUrl = verificationFieldHandlerVars?.ajax_url;
            this.nonce = verificationFieldHandlerVars?.nonce;
            this.form = jQuery(this.field).closest('form');
            this.formId = this.form.find('[name="form_id"]').val();
            this.emailFieldID = "form-field-" + jQuery(this.field).data('email-field');
            this.emailField = jQuery('#' + this.emailFieldID);
            this.sendAgainButton = this.field.parentElement.querySelector('.send-code-again');

            if (this.ajaxUrl && this.nonce) {
                this.init();
            }
        }

        init() {
            if (this.emailField.length) {
                this.toggleFormFieldRequirement(this.emailField,null,true );
                if (this.sendAgainButton) {
                    this.resetSendAgainButtonState(this.sendAgainButton);
                    const codeLength = jQuery(this.field).attr("maxlength");
                    this.sendAgainButton.addEventListener('click', (e) => {
                        this.handleSendAgainClick(e, codeLength);
                    });
                }

                jQuery(document).ajaxComplete((event, xhr, settings) => {
                    console.log(event, xhr, settings)
                    this.handleAjaxComplete(settings, xhr);
                });
            }
        }

        toggleFormFieldRequirement($field, $fieldGroup = false, isRequired = true) {
            if (!$fieldGroup) {
                $fieldGroup = $field.closest('.elementor-field-group');
            }
            if ($field && $fieldGroup) {
                if (isRequired) {
                    $field.attr({
                        "required": "required",
                        "aria-required": "true"
                    });
                    $fieldGroup.addClass(["elementor-field-required", "elementor-mark-required"]);
                } else {
                    $field.removeAttr("required aria-required");
                    $fieldGroup.removeClass(["elementor-field-required", "elementor-mark-required"]);
                }
            }
        }

        handleAjaxComplete(settings, xhr) {
            if (settings.data instanceof FormData) {
                const formData = settings.data;
                const verificationFieldId = this.getFieldIdFromFieldElement(this.field);
                const $verificationFieldGroup = jQuery(this.field).closest('.elementor-field-group');
                if (formData.has('form_id') && formData.get('form_id') === this.formId && verificationFieldId) {
                    if (xhr.responseJSON?.success === false &&
                        xhr.responseJSON.data?.data?.[verificationFieldId]?.code_sent === true) {
                        $verificationFieldGroup.addClass('code-sent');
                        this.toggleFormFieldRequirement(jQuery(this.field), $verificationFieldGroup, true);
                        this.removeFormError();
                    } else if (xhr.responseJSON.success === true) {
                        $verificationFieldGroup.removeClass('code-sent')
                        this.toggleFormFieldRequirement(jQuery(this.field), $verificationFieldGroup, false)
                    }
                }
            }
        }

        handleSendAgainClick(e, codeLength) {
            e.preventDefault();
            e.stopPropagation();
            const email = this.emailField.val();

            const emailValid = this.emailField[0].reportValidity();
            if (!emailValid) {
                this.emailField.focus();
                return;
            }
            this.sendAgainButton.classList.add('loading');
            jQuery.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'evef_send_verification_code',
                    email: email,
                    _ajax_nonce: this.nonce,
                    code_length: codeLength,
                    widget_id: this.formId,
                    post_id: this.form.find('[name="post_id"]').val()
                }
            }).done((data, textStatus, jqXHR) => {
                this.updateSendAgainButtonState('success');
            }).fail((jqXHR, textStatus, errorThrown) => {
                this.updateSendAgainButtonState('error');
            }).always((jqXHR, textStatus, errorThrown) => {
                this.sendAgainButton.classList.remove('loading');
                setTimeout(() => {
                    this.resetSendAgainButtonState();
                }, 30000);
            });
        }

        updateSendAgainButtonState(state) {
            const states = ['normal', 'success', 'error'];
            states.forEach(s => {
                this.sendAgainButton.querySelector(`.${s}`).style.display = s === state ? "" : "none";
            });
        }

        resetSendAgainButtonState() {
            this.updateSendAgainButtonState('normal');
        }

        removeFormError() {
            const formError = document.querySelector(`div[data-id="${this.formId}"] form > .elementor-message`);
            if (formError) {
                formError.remove();
            }
        }

        getFieldIdFromFieldElement(elem) {
            if (elem instanceof Element && elem.id) {
                const originalString = elem.id;
                const prefix = "form-field-";
                return originalString.startsWith(prefix) ? originalString.substring(prefix.length) : originalString;
            }
        }
    }

    const verificationFields = document.querySelectorAll('form .elementor-verification-field');
    verificationFields.forEach(field => new VerificationFieldHandler(field));
});
