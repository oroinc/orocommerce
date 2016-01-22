/*global define*/
define(function(require) {
    'use strict';

    var QuickAddImportValidationComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddImportValidationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            _wid: '',
            containerSelector: '#import-validation',
            cancelButtonSelector: '.cancel-action',
            backButtonSelector: '.back-action',
            errorToggleSelector: '.error-toggle',
            errorVisibleClass: 'errors-visible'
        },

        /**
         * @property {jQuery}
         */
        $container: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.$container = $(this.options.containerSelector);

            this.$container.find(this.options.errorToggleSelector).on('click', _.bind(this.toggleErrors, this));
            this.$container.find(this.options.backButtonSelector).on('click', _.bind(this.backAction, this));
            this.$container.find(this.options.cancelButtonSelector).on('click', _.bind(this.cancelAction, this));
        },

        toggleErrors: function (e) {
                e.preventDefault();
                this.$container.toggleClass(this.options.errorVisibleClass);
        },

        backAction: function (e) {
            var url = $(e.target).attr('data-url');
            widgetManager.getWidgetInstance(this.options._wid, _.bind(this.loadUrl, url));
        },

        loadUrl: function (widget) {
            widget.setUrl(this);
            widget.loadContent();
        },

        cancelAction: function () {
            widgetManager.getWidgetInstance(this.options._wid, _.bind(this.closeWidget));
        },

        closeWidget: function(widget) {
            widget.dispose();
        }
    });

    return QuickAddImportValidationComponent;
});
