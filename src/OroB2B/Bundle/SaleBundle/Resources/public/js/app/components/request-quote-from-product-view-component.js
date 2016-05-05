/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var RequestQuoteFromProductViewComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    RequestQuoteFromProductViewComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.options._sourceElement.on('click', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var el = $(e.target);
            var form = el.closest('form');
            var url = el.data('url');
            var urlOptions = el.data('urloptions');
            var intention = el.data('intention');

            if (!this.validateForm(form)) {
                return;
            }

            this.createRequestQuotePage(url, urlOptions, form);
        },

        /**
         * @param {Object} form
         */
        validateForm: function(form) {
            var component = this;
            var validator;
            var valid = true;

            if (form.data('validator')) {
                validator = form.validate();
                $.each(component.formElements(form), function() {
                    valid = validator.element(this) && valid;
                });
            }

            return valid;
        },

        /**
         * @param {Object} form
         */
        formElements: function(form) {
            return form.find('input, select, textarea').not(':submit, :reset, :image');
        },

        /**
         * @param {String} url
         * @param {Object} lineItemOptions
         * @param {Object} form
         */
        createRequestQuotePage: function(url, lineItemOptions, form) {
            var productItems = {};
            productItems[lineItemOptions.product_id] = [{
                'unit': form.find('select[name="orob2b_product_frontend_line_item[unit]"]').val(),
                'quantity': form.find('input[name="orob2b_product_frontend_line_item[quantity]"]').val()
            }];
            var urlOptions = {
                'product_items': productItems
            };
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {url: routing.generate(url, urlOptions)}, {redirect: true});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            RequestQuoteFromProductViewComponent.__super__.dispose.call(this);
        }
    });

    return RequestQuoteFromProductViewComponent;
});
