const { __, sprintf } = wp.i18n;
elementor.hooks.addFilter(
    `elementor_pro/forms/content_template/field/${evefEditorConfig.field_type_constant}`,
    function (inputField, item, i, settings) {
        const emailFieldId = item[`${evefEditorConfig.email_field_constant}`]
        const isEmailFieldIdValid = settings["form_fields"].some(field => field.custom_id === emailFieldId);
        if(!isEmailFieldIdValid){
            return `<p> ${__('E-Mail field could not be found', 'email-verification-elementor-forms')}</p>`
        }

        const fieldId = `form_field_${i}`;
        const fieldClass = `elementor-field-textual elementor-${evefEditorConfig.field_type_constant}-field elementor-field elementor-size-${settings.input_size} ${item.css_classes}`;
        const maxLength = item[`${evefEditorConfig.code_length_constant}`];
        const pattern = `\\d{${maxLength}}`; // Changed to string
        const placeholder = sprintf(__("Enter your %s-digit code", "email-verification-elementor-forms"), maxLength);

        return `
                <input size="1" type="${item.field_type}" id="${fieldId}" class="${fieldClass}" name="${fieldId}"
                       placeholder="${placeholder}" maxlength="${maxLength}" pattern="${pattern}"
                       data-email-field="${emailFieldId}">
                <span role="button" class="send-code-again" tabindex="0">
                    <span class="normal">${evefEditorConfig.normal_text}</span>
                    <span class="success" style="display:none;">${evefEditorConfig.success_text}</span>
                    <span class="error" style="display:none;">${evefEditorConfig.error_text}</span>
                    ${evefEditorConfig.loader_html}
                </span>`;
    }, 10, 4
);

class VerificationFieldHandlerEditor {
    constructor() {
        this.editor = null;
        this.editedModel = null;
        this.repeaterControl = null
        this.verificationFieldControls = [];
        this.emailFieldIDControls = [];

        this.init();
    }

    setControls() {
        this.verificationFieldControls = this.getControlModelsFromModelByName({
            field_type: evefEditorConfig.field_type_constant
        }, this.repeaterControl)
        this.emailFieldIDControls = [];
        this.verificationFieldControls.forEach(control => {
            const emailFieldIdControls = this.getControlModelsFromModelByName({
                name: evefEditorConfig.email_field_constant
            }, control)
            if (emailFieldIdControls.length) this.emailFieldIDControls.push(emailFieldIdControls[0]);
        })

    }

    updateEmailFieldId() {
        this.setControls();
        if(this.emailFieldIDControls.length) {
            const settingsModel = this.editedModel.get('settings');
            let emailModels = settingsModel.get('form_fields').where({
                field_type: 'email'
            });

            emailModels = _.reject(emailModels, {field_label: ''});

            const emailFields = emailModels.map(model => ({
                id: model.get('custom_id'),
                label: sprintf(__('%s Field', 'elementor-pro'), model.get('field_label'))
            }));

            //this.emailFieldIDControls.set('options', {'': this.emailFieldIDControls.get('options')['']});
            const newOptions = {'': this.emailFieldIDControls[0].model.get('options')['']}
            emailFields.forEach(emailField => {
                newOptions[emailField.id] = emailField.label;
            });
            this.emailFieldIDControls.forEach(control => {
                control.model.set('options', newOptions)
                control.render();
            })
        }
    }

    onFormFieldsChange(changedModel) {
        if (changedModel.get('custom_id')) {
            if (["email", evefEditorConfig.field_type_constant].includes(changedModel.get('field_type'))) {
                this.updateEmailFieldId()
            }
        }
    }

    getControlModelsFromModelByName(filter, model) {
        const views = model?.collection.where(filter);
        const result = []
        views?.forEach(view => {
            const _model = model?.children.findByModelCid(view.cid)
            if (_model) result.push(_model)
        })
        return result
    }


    onPanelShow(panel, model) {
        this.editor = panel.getCurrentPageView();
        this.editedModel = model;
        const repeaterControls = this.getControlModelsFromModelByName({name: 'form_fields'}, this.editor)
        if (!repeaterControls.length) {
            console.error("form_fields control not found")
            return
        }
        this.repeaterControl = repeaterControls[0]

        this.updateEmailFieldId()
        this.repeaterControl.collection
            .on( 'change', this.onFormFieldsChange.bind(this) )
            .on( 'remove', this.onFormFieldsChange.bind(this) );
    }

    init() {
        elementor.hooks.addAction('panel/open_editor/widget/form', this.onPanelShow.bind(this));
    }
}

// Instantiate the class to ensure it runs when needed
new VerificationFieldHandlerEditor();
