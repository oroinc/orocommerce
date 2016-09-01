define([
    'jquery',
    'backbone',
    'underscore',
    'oroui/js/mediator'
], function($, Backbone, _, mediator) {
    'use strict';

    /**
     * @export  orointegration/js/channel-view
     * @class   orointegration.channelView
     * @extends Backbone.View
     */
    return Backbone.View.extend({

        requiredOptions: ['methodSelectSelector', 'buttonSelector', 'updateFlag', 'methods'],

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }
            this.form = $(this.el).parents("form:first").get(0);
            if (this.form === undefined) {
                throw new TypeError('Form not found');
            }
            this.form = $(this.form);
            this.methodSelect = $(this.el).find(this.options.methodSelectSelector);
            this.button = $(this.el).find(options.buttonSelector);

            this.button.on('click', _.bind(this.changeHandler, this));

            var elements = this.form.find(".orob2b-shipping-rule-method-configs-collection .row-oro.oro-multiselect-holder");
            this.methodCount = elements.length;
            var self = this;
            elements.each(function(index, element) {
                $(element).parent().on('content:remove', function(e) {
                    self.updateMethodSelector(element);
                });
            });

            this.updateMethodSelector();
        },

        /**
         * Check whenever form change and shows confirmation
         */
        changeHandler: function() {
            var $form = this.form;
            var data = $form.serializeArray();
            var url = $form.attr('action');
            var value = $(this.el).find(this.options.methodSelectSelector).val();
            data.push({
                'name': 'oro_shipping_rule[methodConfigs][' + this.methodCount + '][method]',
                'value': value
            });
            this.methodCount++;
            data.push({
                'name': this.options.updateFlag,
                'value': true
            });
            mediator.execute('submitPage', {
                url: url,
                type: $form.attr('method'),
                data: $.param(data)
            });
        },

        updateMethodSelector: function(removedElement) {
            var elements = this.form.find(".orob2b-shipping-rule-method-configs-collection .row-oro.oro-multiselect-holder");
            var methods = [];
            var self = this;

            elements.each(function(index, element) {
                if (removedElement && self.getMethod(element) === self.getMethod(removedElement)) {
                    return;
                }
                methods.push($(element).find('input[data-name="field__method"]').val());
            });
            if (methods.length === Object.keys(this.options.methods).length) {
                $(this.el).hide();
                return;
            }
            this.methodSelect.empty(); // remove old options
            $.each(self.options.methods, function(value, label) {
                if ($.inArray(value, methods) === -1) {
                    self.methodSelect.append($("<option></option>").attr("value", value).text(label));
                }
            });

            $.uniform.update(this.methodSelect);
            $(this.el).show();
        },


        getMethod: function(element) {
            return $(element).find('input[data-name="field__method"]').val();
        }
    });
});
