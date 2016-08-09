define(function(require) {
    'use strict';

    var QuickAddImportValidationComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    QuickAddImportValidationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            _wid: '',
            containerSelector: '#import-validation',
            cancelButtonSelector: 'button:reset',
            navigateButtonSelector: 'button[data-url]',
            errorToggleSelector: '.error-toggle',
            errorVisibleClass: 'errors-visible'
        },

        /**
         * @property {jQuery}
         */
        $container: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$container = $(this.options.containerSelector);

            this.$container.find(this.options.errorToggleSelector).on('click', _.bind(this.toggleErrors, this));
            this.$container.find(this.options.navigateButtonSelector).on('click', _.bind(this.navigateAction, this));
            this.$container.find(this.options.cancelButtonSelector).on('click', _.bind(this.cancelAction, this));
            mediator.on('quick-add-form-button-component:submit', _.bind(this.onSubmitAction, this));
        },

        toggleErrors: function(e) {
            e.preventDefault();
            this.$container.toggleClass(this.options.errorVisibleClass);
        },

        navigateAction: function(e) {
            var url = $(e.target).attr('data-url');
            widgetManager.getWidgetInstance(this.options._wid, _.bind(this.loadUrl, url));
        },

        loadUrl: function(widget) {
            widget.setUrl(this);
            widget.loadContent();
        },

        cancelAction: function() {
            widgetManager.getWidgetInstance(this.options._wid, _.bind(this.closeWidget));
        },

        onSubmitAction: function() {
            widgetManager.getWidgetInstance(this.options._wid, _.bind(this.onSubmit));
        },

        onSubmit: function(widget) {
            widget.on('contentLoad', function(content) {
                if (_.has(content, 'redirectUrl')) {
                    widget.dispose();
                    mediator.execute('showLoading');
                    mediator.execute('redirectTo', {url: content.redirectUrl}, {redirect: true});
                }
            });
        },

        closeWidget: function(widget) {
            widget.dispose();
        },

        dispose: function() {
            mediator.off(null, null, this);
        }
    });

    return QuickAddImportValidationComponent;
});
