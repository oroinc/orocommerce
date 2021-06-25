define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/delete-confirmation'
], function($, Backbone, _, __, mediator, DeleteConfirmation) {
    'use strict';

    /**
     * @export  orointegration/js/channel-view
     * @class   orointegration.channelView
     * @extends Backbone.View
     */
    const ContentWidgetView = Backbone.View.extend({
        /**
         * @type {Object}
         */
        options: {
            updateMarker: 'formUpdateMarker',
            formSelector: null,
            typeSelector: null,
            fieldsSets: [] // array of fields that should be submitted for form update
        },

        /**
         * @type {Array}
         */
        requiredOptions: ['formSelector', 'typeSelector'],

        /**
         * @inheritdoc
         */
        constructor: function ContentWidgetView(options) {
            ContentWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            const requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            this.options = _.defaults(options || {}, this.options);

            $(this.options.typeSelector).on('change', this.changeHandler.bind(this));

            this.memoizeValue(this.options.typeSelector);
        },

        /**
         * Check whenever form change and shows confirmation
         * @param {$.Event} e
         */
        changeHandler: function(e) {
            const $el = $(e.currentTarget);

            if ($el.data('cancelled') === true) {
                $el.data('cancelled', false);
            } else {
                const prevVal = $el.data('current');

                if (this.isEmpty()) {
                    this.processChange($el);
                } else {
                    const confirm = new DeleteConfirmation({
                        title: __('oro.cms.change_type.confirmation.title'),
                        okText: __('Yes'),
                        content: __('oro.cms.change_type.confirmation.body')
                    });

                    confirm.on('ok', () => {
                        this.processChange($el);
                    });

                    confirm.on('cancel', () => {
                        $el.data('cancelled', true).val(prevVal).trigger('change');
                        this.memoizeValue($el);
                    });

                    confirm.open();
                }
            }
        },

        /**
         * Updates form via ajax, renders dynamic fields
         *
         * @param {$.element} $el
         */
        processChange: function($el) {
            this.memoizeValue($el);

            const $form = $(this.options.formSelector);
            const url = $form.attr('action');
            const fieldsSet = this.options.fieldsSets;

            const data = _.filter($form.serializeArray(), function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });
            data.push({name: this.options.updateMarker, value: $el.attr('name')});

            mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
        },

        /**
         * Check whenever form fields are empty
         *
         * @returns {boolean}
         */
        isEmpty: function() {
            const fieldsSet = this.options.fieldsSets;
            const fields = $(this.options.formSelector)
                .find('input[type="text"],textarea')
                .filter(function() {
                    return this.name && _.indexOf(fieldsSet, this.name) === -1 && this.value !== '';
                });

            return !fields.length;
        },

        /**
         * Remember current value in case if in future we will need to undo changes
         *
         * @param {$.element} el
         */
        memoizeValue: function(el) {
            const $el = $(el);
            $el.data('current', $el.val());
        }
    });

    return ContentWidgetView;
});
