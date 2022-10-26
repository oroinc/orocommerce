define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options');
    const messenger = require('oroui/js/messenger');

    const QuickAddComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            componentSelector: '[name$="[component]"]',
            additionalSelector: '[name$="[additional]"]',
            componentButtonSelector: '.component-button',
            componentPrefix: 'quick-add',
            isOptimized: false
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {QuickAddCollection}
         */
        productsCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function QuickAddComponent(options) {
            QuickAddComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$form = this.options._sourceElement;

            this.$form.on('click', this.options.componentButtonSelector, _.bind(this.fillComponentData, this));

            if (this.options.isOptimized) {
                this.productsCollection = this.options.productsCollection;
                this.$form.on('submit', _.bind(this.onSubmit, this));
            }

            mediator.on(this.options.componentPrefix + ':submit', this.submit, this);
        },

        fillComponentData: function(e) {
            const $element = $(e.target);
            this.submit($element.data('component-name'), $element.data('component-additional'));
        },

        /**
         * @param {String} component
         * @param {String} additional
         */
        submit: function(component, additional) {
            this.$form.find(this.options.componentSelector).val(component);
            this.$form.find(this.options.additionalSelector).val(additional);
            this.$form.submit();
        },

        onSubmit(e) {
            e.preventDefault();

            const quickAddRows = [];
            _.each(this.productsCollection.models, model => {
                if (model.get('sku')) {
                    const {sku, unit, quantity} = model.attributes;
                    quickAddRows.push({
                        productSku: sku,
                        productUnit: unit,
                        productQuantity: quantity
                    });
                }
            });

            const newFormData = new FormData();
            const formName = this.$form.attr('name');
            for (const row of this.$form.serializeArray()) {
                if (row.name.indexOf(formName + '[products]') === -1) {
                    newFormData.append(row.name, row.value);
                }
            }

            newFormData.append(formName + '[products]', JSON.stringify(quickAddRows));

            const ajaxOptions = formToAjaxOptions(this.$form, {
                contentType: false,
                beforeSend: (xhr, options) => {
                    options.data = newFormData;
                },
                success: response => {
                    if (_.has(response, 'redirectUrl')) {
                        mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
                        return;
                    }

                    if (response.messages) {
                        _.each(response.messages, (messages, type) => {
                            _.each(messages, message => {
                                messenger.notificationMessage(type, message);
                            });
                        });
                    }

                    if (response.collection) {
                        _.each(response.collection.errors, error => {
                            messenger.notificationMessage('error', error.message);
                        });

                        if (response.collection.items && response.collection.items.length) {
                            this.productsCollection.addQuickAddRows(response.collection.items, {strategy: 'replace'});
                        }
                    }
                }
            });

            $.ajax(ajaxOptions);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.options.isOptimized) {
                this.$form.off('submit', _.bind(this.onSubmit, this));
            }

            mediator.off(this.options.componentPrefix + ':submit', this.submit, this);
            QuickAddComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddComponent;
});
