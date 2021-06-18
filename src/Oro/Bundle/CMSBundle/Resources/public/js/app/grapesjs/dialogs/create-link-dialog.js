import _ from 'underscore';
import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import manageFocus from 'oroui/js/tools/manage-focus';
import Modal from 'oroui/js/modal';
import template from 'tpl-loader!orocms/templates/create-link-form.html';

const ENTER_KEY_CODE = 13;
const EVENTS = {
    OK: 'ok'
};

const CreateLinkModal = Modal.extend({
    options: {
        title: __('oro.cms.wysiwyg.create_link_dialog.add_new_link'),
        cancelText: __('Close'),
        okText: __('oro.cms.wysiwyg.create_link_dialog.insert'),
        focusOk: false,
        initLayoutOptions: {}
    },

    events() {
        const events = {};

        events['keydown'] = e => {
            if (e.keyCode === ENTER_KEY_CODE) {
                this.handlerClick(EVENTS.OK, e);
            }
        };

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function CreateLinkModal(options) {
        CreateLinkModal.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.options = _.extend({}, this.options, options || {});

        if (this.options['content'] === void 0) {
            this.options['content'] = template(this.getTemplateData());
        }


        CreateLinkModal.__super__.initialize.call(this, this.options);
    },

    getTemplateData() {
        const data = CreateLinkModal.__super__.getTemplateData.call(this);
        const options = _.pick(this.options, 'controlsValues');

        data.controlsNames = {
            href: `${this.cid}href`,
            text: `${this.cid}text`,
            title: `${this.cid}title`,
            target: `${this.cid}target`
        };

        data.controlsValues = {
            text: '',
            href: '',
            title: '',
            target: ''
        };

        if (options.controlsValues) {
            data.controlsValues = Object.assign(data.controlsValues, options.controlsValues);
        }

        return data;
    },

    /**
     * @inheritdoc
     */
    open(...args) {
        CreateLinkModal.__super__.open.apply(this, ...args);

        this.getLayoutElement().attr('data-layout', 'separate');
        this.initLayout(this.options.initLayoutOptions || {});
        return this;
    },

    onModalShown() {
        CreateLinkModal.__super__.onModalShown.call(this);
        manageFocus.focusTabbable(this.$el);
    },

    /**
     * Handler for button click
     *
     *  @param {String} triggerKey
     *  @param {jQuery.Event} event
     */
    handlerClick(triggerKey, event) {
        if (triggerKey === EVENTS.OK) {
            const $form = this.$('form');
            const validator = $form.validate();

            Object.assign(validator.settings, {
                rules: {
                    [`${this.cid}href`]: 'NotBlank',
                    [`${this.cid}text`]: 'NotBlank'
                }
            });

            const $hrefField = $(`[name="${this.cid}href"]`);

            $hrefField.val(
                $hrefField.val().trim()
            );

            if (validator.form()) {
                const data = $form.serializeArray().reduce((acc, param) => {
                    const key = param['name'].replace(`${this.cid}`, '');
                    const val = param['value'];

                    if (key === 'href') {
                        acc[key] = val.replace(/\s/g, '+');
                    } else if (val) {
                        acc[key] = val;
                    }

                    return acc;
                }, {});

                this.trigger('create-link-dialog:valid', data);
                CreateLinkModal.__super__.handlerClick.call(this, triggerKey, event);
            }
        } else {
            CreateLinkModal.__super__.handlerClick.call(this, triggerKey, event);
        }
    },

    isValid() {
        return this.$('form').form();
    }
});

export default CreateLinkModal;
