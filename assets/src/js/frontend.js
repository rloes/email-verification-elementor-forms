document.addEventListener('DOMContentLoaded', () => {
    class VerificationFieldHandler {
        TIMEOUT_IN_SEC = 30

        constructor(field) {
            this.$field = jQuery(field);
            this.ajaxUrl = evefFrontendConfig?.ajax_url;
            this.nonce = evefFrontendConfig?.nonce;
            this.$form = this.$field.closest('form');
            this.formId = this.$form.find('[name="form_id"]').val();
            const emailFieldID = "form-field-" + this.$field.data('email-field');
            this.$emailField = jQuery('#' + emailFieldID);
            this.sendAgainButton = this.$field[0].parentElement.querySelector('.send-code-again');
            this.ajaxCompleteHandler = (event, xhr, settings) => this.handleAjaxComplete(settings, xhr);
            this.timer = null;
            this.fieldId = this.getFieldIdFromFieldElement(this.$field[0])
            if (this.ajaxUrl && this.nonce && this.$emailField && this.sendAgainButton) {
                this.init();
            } else {
                console.error("EVEF: Not everything necessary could be defined", this)
            }
        }

        /**
         * Steps:
         * 1. require email field
         * 2. unrequire verification field
         * 3. add event listener for send again button
         * 4. add listener to ajax events
         */
        init() {
            if (this.$emailField.length) {
                this.toggleFormFieldRequirement(this.$emailField, false, true);
                this.toggleFormFieldRequirement(this.$field, false, false)
                if (this.sendAgainButton) {
                    this.resetSendAgainButtonState(this.sendAgainButton);
                    this.sendAgainButton.addEventListener('click', (e) => {
                        this.handleSendAgain(e);
                    });
                    this.sendAgainButton.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            this.handleSendAgain(e);
                        }
                    });
                }

                jQuery(document).ajaxComplete(this.ajaxCompleteHandler);
            }
        }

        /**
         * sets/unsets requirement of a formField
         * @param $field
         * @param $fieldGroup - optional, will be retrieved if not given
         * @param isRequired
         */
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

        /**
         * if ajax isnt complete, but verification code was sent:
         *  - make verification field required
         *  - remove general form error
         * @param settings
         * @param xhr
         */
        handleAjaxComplete(settings, xhr) {
            if (settings.data instanceof FormData) {
                const formData = settings.data;
                const verificationFieldId = this.fieldId;
                const $verificationFieldGroup = this.$field.closest('.elementor-field-group');
                if (formData.has('form_id') && formData.get('form_id') === this.formId && verificationFieldId) {
                    if (xhr.responseJSON?.success === false &&
                        xhr.responseJSON.data?.data?.[verificationFieldId]?.code_sent === true) {
                        $verificationFieldGroup.addClass('code-sent');
                        this.toggleFormFieldRequirement(this.$field, $verificationFieldGroup, true);
                        this.removeFormError();
                    } else if (xhr.responseJSON.success === true) {
                        $verificationFieldGroup.removeClass('code-sent')
                        this.toggleFormFieldRequirement(this.$field, $verificationFieldGroup, false)
                    }
                }
            }
        }

        /**
         * click event, that triggers resent of the verification email.
         * Only works every 30s per ip address
         * @param e
         */
        handleSendAgain(e) {
            e.preventDefault();
            e.stopPropagation();
            // Only go through if button is in normal state
            const isSendAgainAvailable = this.sendAgainButton.querySelector('.normal').style.display !== "none"
            if (!isSendAgainAvailable) return;
            const email = this.$emailField.val();

            const emailValid = this.$emailField[0].reportValidity();
            if (!emailValid) {
                this.$emailField.focus();
                return;
            }
            let timeout = this.TIMEOUT_IN_SEC
            this.sendAgainButton.classList.add('loading');
            jQuery.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'evef_send_verification_code',
                    email: email,
                    _ajax_nonce: this.nonce,
                    widget_id: this.formId,
                    post_id: this.$form.find('[name="post_id"]').val(),
                    field_id: this.fieldId
                }
            }).done((data, textStatus, jqXHR) => {
                if(data.timeout){
                    timeout = data.timeout;
                }
                if(data.success) {
                    this.updateSendAgainButtonState('success', timeout);
                }else{
                    this.updateSendAgainButtonState('error');
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                console.error("EVEF: Ajax request failed", textStatus, errorThrown);
                this.updateSendAgainButtonState('error');
            }).always((jqXHR, textStatus, errorThrown) => {
                this.sendAgainButton.classList.remove('loading');
                setTimeout(() => {
                    this.resetSendAgainButtonState();
                }, timeout * 1000);
            });
        }

        /**
         * Inside the sent-again there are all markup included, hide and reveal accordingly
         * @param state
         * @param timeout
         */
        updateSendAgainButtonState(state, timeout = this.TIMEOUT_IN_SEC) {
            const states = ['normal', 'success', 'error'];
            states.forEach(s => {
                const element = this.sendAgainButton.querySelector(`.${s}`);
                const isActiveState = (s === state);
                element.style.display = isActiveState ? "" : "none";
                element.setAttribute('aria-hidden', isActiveState ? 'false' : 'true');
            });

            if (state === 'success') {
                this.startTimer(timeout);
            } else if (state === 'normal' && this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }

        resetSendAgainButtonState() {
            this.updateSendAgainButtonState('normal');
        }

        /**
         * After first send, it will return an error for code sent. By
         */
        removeFormError() {
            const formError = document.querySelector(`div[data-id="${this.formId}"] form > .elementor-message`);
            if (formError) {
                formError.remove();
            }
        }

        /**
         * The custom-id of a form field is only
         * @param elem
         * @returns {string}
         */
        getFieldIdFromFieldElement(elem) {
            if (elem instanceof Element && elem.id) {
                const originalString = elem.id;
                const prefix = "form-field-";
                return originalString.startsWith(prefix) ? originalString.substring(prefix.length) : originalString;
            }
        }

        /**
         * Send email again shouldn't be spammed,
         * this implements the cooldown visually in success text
         */
        startTimer(remainingTime = this.TIMEOUT_IN_SEC) {
            let _remainingTime = remainingTime;
            const timerElement = this.sendAgainButton.querySelector('.timer')
            timerElement.textContent = _remainingTime;
            this.timer = setInterval(() => {
                _remainingTime -= 1;
                timerElement.textContent = _remainingTime;
                if (_remainingTime <= 0) {
                    clearInterval(this.timer);
                    this.timer = null;
                    timerElement.textContent = "";
                    this.resetSendAgainButtonState();
                }
            }, 1000);
        }

        destroy() {
            this.sendAgainButton.removeEventListener('click', this.handleSendAgain);
            this.sendAgainButton.removeEventListener('keydown', this.handleSendAgain);
            jQuery(document).off('ajaxComplete', this.ajaxCompleteHandler);
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }
    }

    const fieldHandlers = [];

    function initializeVerificationHandlers() {
        const verificationFields = document.querySelectorAll('form .elementor-evef-verification-field');
        const handlersToRemove = [];

        // Check and clean up removed fields
        fieldHandlers.forEach(handler => {
            if (!document.contains(handler.field)) {
                handler.destroy();
                handlersToRemove.push(handler);
            }
        });

        handlersToRemove.forEach(handler => {
            const index = fieldHandlers.indexOf(handler);
            if (index !== -1) {
                fieldHandlers.splice(index, 1);
            }
        });

        // Initialize handlers for new fields
        verificationFields.forEach(field => {
            if (!handlerExistsForField(field)) {
                const handler = new VerificationFieldHandler(field);
                fieldHandlers.push(handler);
            }
        });
    }

    function handlerExistsForField(field) {
        return fieldHandlers.some(handler => handler.field === field);
    }

    jQuery(window).on('elementor/frontend/init', () => {
        elementorFrontend.hooks.addAction('frontend/element_ready/form.default', ($scope) => {
            initializeVerificationHandlers();
        });
    });


});

