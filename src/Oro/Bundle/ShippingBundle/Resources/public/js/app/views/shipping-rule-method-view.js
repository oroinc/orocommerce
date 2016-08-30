define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, Backbone, _, __, mediator) {
    'use strict';

    /**
     * @export  orointegration/js/channel-view
     * @class   orointegration.channelView
     * @extends Backbone.View
     */
    return Backbone.View.extend({

        requiredOptions: ['methodSelector', 'buttonSelector', 'methodCount', 'updateFlag'],

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
            $(this.el).find(options.buttonSelector).on('click', _.bind(this.changeHandler, this));
        },

        /**
         * Check whenever form change and shows confirmation
         */
        changeHandler: function() {
            var $form = this.form;
            var data = $form.serializeArray();
            var url = $form.attr('action');
            var value = $(this.el).find(this.options.methodSelector).val();
            data.push({
                'name': 'oro_shipping_rule[methodConfigs][' + this.options.methodCount + '][method]',
                'value': value
            });
            this.options.methodCount++;
            data.push({
                'name': this.options.updateFlag,
                'value': true
            });
            mediator.execute('submitPage', {
                url: url,
                type: $form.attr('method'),
                data: $.param(data)
            });
        }
    });
});
