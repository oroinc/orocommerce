define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const actionsTemplate = require('tpl-loader!oroconsent/templates/frontend-dialog/dialog-actions.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');

    const BaseConsentItemView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function BaseConsentItemView(options) {
            return BaseConsentItemView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$form = this.$el.closest('form');
            this.$valueField = this.$form.find(this.consentsFieldSelector);
            BaseConsentItemView.__super__.initialize.call(this, options);

            this._updateFromFieldToElement();
        },

        /**
         * Render custom consent dialog widget
         */
        renderDialogWidget: function() {
            this._toggleSubmitButtonOnce = true;
            this.subview('popup', new FrontendDialogWidget({
                autoRender: true,
                url: this.cmsPageData.url,
                title: this.consentTitle,
                simpleActionTemplate: !this.$valueField.length,
                renderActionsFromTemplate: true,
                actionsTemplate: actionsTemplate,
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

            const handleFirst = state => {
                this.changeCheckboxState(state);
                this.$valueField.trigger('change');
                this.stopListening(this.subview('popup'));
            };
            this.listenToOnce(this.subview('popup'), {
                'frontend-dialog:accept': handleFirst.bind(void 0, true),
                'frontend-dialog:cancel': handleFirst.bind(void 0, false),
                'frontend-dialog:close': handleFirst.bind(void 0, false),
                'dispose': handleFirst.bind(void 0, false),
                'renderComplete': this.onRenderComplete
            });
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
                // Stop Propagation if popup agree is required
                event.stopPropagation();
                this.renderDialogWidget();
            }

            // Disable consent if user just uncheck checkbox
            if (!$(event.target).is(':checked') || !this.cmsPageData) {
                this._updateFormElementToField($(event.target).is(':checked'));
                this.$valueField.trigger('change');
            }
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
            let oldValue = this.$valueField.val();
            if (_.isEmpty(oldValue)) {
                oldValue = '[]';
            }

            const value = _.indexBy(JSON.parse(oldValue), 'consentId');

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
        },

        /**
         * Update consent checkbox state from consent input data
         *
         * @private
         */
        _updateFromFieldToElement: function() {
            const values = this.$valueField.val();
            if (_.isEmpty(values)) {
                return;
            }

            if (_.has(_.indexBy(JSON.parse(values), 'consentId'), this.consentId)) {
                this.changeCheckboxState(true);
            }
        },

        /**
         * Listen renderComplete event
         */
        onRenderComplete: function() {
            this.disableSubmitElement();
            this.subview('popup').widget
                .on('scroll', this.onScroll.bind(this));
        },

        /**
         * Disable submit button when consent content height include margins is bigger than widget height
         */
        disableSubmitElement: function() {
            const popup = this.subview('popup');
            const disable = $(popup.el).outerHeight(true) > popup.widget.outerHeight();

            this.updateSubmitElementState(disable);
        },

        /**
         * Submit element should be active when text was scrolled to the down
         */
        onScroll: function() {
            const consentIncludeMargin = Math.floor($(this.subview('popup').el).outerHeight(true));
            const widget = this.subview('popup').widget;
            const isEndOfConsent = consentIncludeMargin < Math.floor(widget.outerHeight() + widget.scrollTop());

            if (this._toggleSubmitButtonOnce) {
                this.updateSubmitElementState(!isEndOfConsent);
            }

            if (isEndOfConsent) {
                this._toggleSubmitButtonOnce = false;
            }
        },

        /**
         * Update state of the submit element
         *
         * @param {Boolean} state
         */
        updateSubmitElementState: function(state) {
            const element = this.subview('popup').actionsEl
                .find('[type=submit]');

            if (element.length) {
                element.toggleClass('disabled', state);
                element.attr('disabled', state);
            }
        }
    });

    return BaseConsentItemView;
});
