define(function(require) {
    'use strict';

    var ConsentsGroupView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');
    var Modal = require('oroui/js/modal');

    ConsentsGroupView = BaseView.extend({
        /**
         * @inheritDoc
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
        confirmModalOkButtonClass: 'btn ok',

        /**
         * @property {String}
         */
        confirmModalCancelButtonClass: 'btn cancel btn--info',

        /**
         * @inheritDoc
         */
        constructor: function ConsentsGroupView() {
            ConsentsGroupView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.initModal();
            this.$form = this.$el.closest('form');
            this.$consents = this.$el.find('[data-role="consent-checkbox"]');
            this.delegateEvents();
            this._removeValidationRules();

            ConsentsGroupView.__super__.initialize.apply(this, arguments);
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
                cancelButtonClass: this.confirmModalCancelButtonClass
            });

            this.listenTo(this.confirmModal, 'ok', _.bind(this._onModalConfirmed, this));
        },

        /**
         * Create events listeners
         */
        delegateEvents: function() {
            ConsentsGroupView.__super__.delegateEvents.apply(this, arguments);

            if (this.$form && this.$form.length) {
                this.$form.on('submit' + this.eventNamespace(), _.bind(this._onFormSubmit, this));
            }
        },

        /**
         * Remove events listeners
         */
        undelegateEvents: function() {
            if (this.$form && this.$form.length) {
                this.$form.off(this.eventNamespace());
            }

            ConsentsGroupView.__super__.undelegateEvents.apply(this, arguments);
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
            var needConfirm = this.$consents.filter(function() {
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
         * @inheritDoc
         */
        dispose: function() {
            this.undelegateEvents();

            delete this.$form;
            delete this.$consents;

            ConsentsGroupView.__super__.dispose.apply(this, arguments);
        }
    });

    return ConsentsGroupView;
});
