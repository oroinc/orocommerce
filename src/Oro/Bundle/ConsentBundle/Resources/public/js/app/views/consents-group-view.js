define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');
    const Modal = require('oroui/js/modal');

    const ConsentsGroupView = BaseView.extend({
        /**
         * @inheritdoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'confirmModalTitle', 'confirmModalContent', 'confirmModalOkText',
            'confirmModalCancelText', 'confirmModalOkButtonClass',
            'confirmModalCancelButtonClass'
        ]),

        /**
         * @property {View}
         */
        confirmModal: Modal,

        /**
         * @property {String}
         */
        confirmModalTitle: _.__('oro.consent.frontend.confirm_modal.title'),

        /**
         * @property {String}
         */
        confirmModalContent: _.__('oro.consent.frontend.confirm_modal.message'),

        /**
         * @property {String}
         */
        confirmModalOkText: _.__('oro.consent.frontend.confirm_modal.ok'),

        /**
         * @property {String}
         */
        confirmModalCancelText: _.__('oro.consent.frontend.confirm_modal.cancel'),

        /**
         * @property {String}
         */
        confirmModalOkButtonClass: 'btn ok btn--info',

        /**
         * @property {String}
         */
        confirmModalCancelButtonClass: 'btn cancel',

        /**
         * @inheritdoc
         */
        constructor: function ConsentsGroupView(options) {
            ConsentsGroupView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.initModal();
            this.$form = this.$el.closest('form');
            this.$consents = this.$el.find('[data-role="consent-checkbox"]');
            this.delegateEvents();
            this._removeValidationRules();

            ConsentsGroupView.__super__.initialize.call(this, options);
        },

        /**
         * Create modal view
         */
        initModal: function() {
            this.confirmModal = new this.confirmModal({
                title: this.confirmModalTitle,
                content: this.confirmModalContent,
                okText: this.confirmModalOkText,
                cancelText: this.confirmModalCancelText,
                okButtonClass: this.confirmModalOkButtonClass,
                cancelButtonClass: this.confirmModalCancelButtonClass,
                disposeOnHidden: false
            });

            this.listenTo(this.confirmModal, 'ok', this._onModalConfirmed.bind(this));
        },

        /**
         * Create events listeners
         */
        delegateEvents: function(events) {
            ConsentsGroupView.__super__.delegateEvents.call(this, events);

            if (this.$form && this.$form.length) {
                this.$form.on('submit' + this.eventNamespace(), this._onFormSubmit.bind(this));
            }
        },

        /**
         * Remove events listeners
         */
        undelegateEvents: function() {
            if (this.$form && this.$form.length) {
                this.$form.off(this.eventNamespace());
            }

            ConsentsGroupView.__super__.undelegateEvents.call(this);
        },

        /**
         * Remove base validation rules for required consent
         */
        _removeValidationRules: function() {
            this.$consents.removeAttr('data-validation');
        },

        /**
         * Form submit handler
         *
         * @param event
         * @private
         */
        _onFormSubmit: function(event) {
            const needConfirm = this.$consents.filter(function() {
                return this.defaultChecked && $(this).is(':not(:checked)');
            });

            if (needConfirm.length) {
                event.preventDefault();
                this.confirmModal.open();
            }
        },

        /**
         * Modal confirmation handler
         *
         * @private
         */
        _onModalConfirmed: function() {
            this.undelegateEvents();
            this.$form.submit();

            this.delegateEvents();
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            this.undelegateEvents();

            delete this.$form;
            delete this.$consents;

            if (this.confirmModal) {
                this.confirmModal.dispose();
                delete this.confirmModal;
            }

            ConsentsGroupView.__super__.dispose.call(this);
        }
    });

    return ConsentsGroupView;
});
