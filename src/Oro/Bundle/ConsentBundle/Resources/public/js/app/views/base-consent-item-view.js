define(function(require) {
    'use strict';

    var BaseConsentItemView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');

    BaseConsentItemView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'consentId', 'consentTitle', 'consentCheckboxSelector',
            'consentsFieldSelector', 'cmsPageData', 'required'
        ]),

        /**
         * View events
         *
         * @property {Object}
         */
        events: {
            'change [data-role="consent-checkbox"]': '_onChecked',
            'click a': '_onLinkClick'
        },

        /**
         * @property {Number}
         */
        consentId: null,

        /**
         * @property {String}
         */
        consentTitle: null,

        /**
         * @property {String}
         */
        consentCheckboxSelector: '[data-role="consent-checkbox"]',

        /**
         * @property {String}
         */
        consentsFieldSelector: '[data-name="field__customer-consents"]',

        /**
         * @property {Object}
         */
        cmsPageData: null,

        /**
         * @property {Object}
         */
        required: null,

        /**
         * @property {jQuery.Element}
         */
        $form: null,

        /**
         * @property {jQuery.Element}
         */
        $valueField: null,

        /**
         * @inheritDoc
         */
        constructor: function BaseConsentItemView() {
            return BaseConsentItemView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$form = this.$el.closest('form');
            this.$valueField = this.$form.find(this.consentsFieldSelector);
            BaseConsentItemView.__super__.initialize.apply(this, arguments);

            this._updateFromFieldToElement();
        },

        /**
         * Render custom consent dialog widget
         */
        renderDialogWidget: function() {
            this.subview('popup', new FrontendDialogWidget({
                autoRender: true,
                url: this.cmsPageData.url,
                title: this.consentTitle,
                simpleActionTemplate: !this.$valueField.length,
                renderActionsFromTemplate: true,
                staticPage: true,
                fullscreenMode: false,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    width: 800,
                    dialogClass: 'consent-dialog-widget'
                }
            }));

            this.subview('popup')
                .once('frontend-dialog:accept', _.bind(this.changeCheckboxState, this, true))
                .once('frontend-dialog:cancel frontend-dialog:close', _.bind(this.changeCheckboxState, this, false));
        },

        /**
         * Change state of consent checkbox
         *
         * @param {Boolean} state
         */
        changeCheckboxState: function(state) {
            this.$(this.consentCheckboxSelector).prop('checked', state);
            this._updateFormElementToField(state);
        },

        /**
         * Handle click on consent checkbox
         *
         * @param {Event} event
         * @private
         */
        _onChecked: function(event) {
            if ($(event.target).is(':checked') && this.cmsPageData) {
                this.renderDialogWidget();
            }
            this._updateFormElementToField($(event.target).is(':checked'));
        },

        /**
         * Handle click on consent link
         *
         * @param {Event} event
         * @private
         */
        _onLinkClick: function(event) {
            event.preventDefault();
            if (this.cmsPageData) {
                this.renderDialogWidget();
            }
        },

        /**
         * Update consent input data from consent items
         *
         * @param {Boolean} state
         * @private
         */
        _updateFormElementToField: function(state) {
            var oldValue = this.$valueField.val();
            if (_.isEmpty(oldValue)) {
                oldValue = '[]';
            }

            var value = _.indexBy(JSON.parse(oldValue), 'consentId');

            if (state) {
                value[this.consentId] = {
                    consentId: this.consentId,
                    cmsPageId: this.cmsPageData ? this.cmsPageData.id : null
                };
            } else {
                if (value[this.consentId]) {
                    delete value[this.consentId];
                }
            }

            this.$valueField.val(JSON.stringify(_.toArray(value)));
            this.$valueField.trigger('change');
        },

        /**
         * Update consent checkbox state from consent input data
         *
         * @private
         */
        _updateFromFieldToElement: function() {
            var values = this.$valueField.val();
            if (_.isEmpty(values)) {
                return;
            }

            if (_.has(_.indexBy(JSON.parse(values), 'consentId'), this.consentId)) {
                this.changeCheckboxState(true);
            }
        }
    });

    return BaseConsentItemView;
});
