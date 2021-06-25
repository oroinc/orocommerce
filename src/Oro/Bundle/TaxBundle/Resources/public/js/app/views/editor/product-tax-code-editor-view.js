define(function(require) {
    'use strict';

    const AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    const _ = require('underscore');
    require('jquery.select2');

    const ProductTaxCodeEditorView =
        AbstractRelationEditorView.extend(/** @exports ProductTaxCodeEditorView.prototype */{
            /**
             * @inheritdoc
             */
            constructor: function ProductTaxCodeEditorView(options) {
                ProductTaxCodeEditorView.__super__.constructor.call(this, options);
            },

            /**
             * @inheritdoc
             */
            initialize: function(options) {
                ProductTaxCodeEditorView.__super__.initialize.call(this, options);
                if (options.value_field_name || options.ignore_value_field_name) {
                    this.valueFieldName = options.value_field_name;
                } else {
                    throw new Error('`value_field_name` option is required');
                }
            },

            getInitialResultItem: function() {
                return {
                    id: this.getModelValue(),
                    label: this.model.get(this.fieldName)
                };
            },

            getSelect2Options: function() {
                const options = _.omit(ProductTaxCodeEditorView.__super__.getSelect2Options.call(this), 'data');

                return _.extend(options, {
                    allowClear: true,
                    noFocus: true,
                    formatSelection: item => {
                        return item.label;
                    },
                    formatResult: item => {
                        return item.label;
                    },
                    initSelection: (element, callback) => {
                        callback(this.getInitialResultItem());
                    },
                    query: options => {
                        this.currentTerm = options.term;
                        if (this.currentRequest && this.currentRequest.term !== '' &&
                            this.currentRequest.state() !== 'resolved') {
                            this.currentRequest.abort();
                        }
                        const autoCompleteUrlParameters = _.extend(this.model.toJSON(), {
                            term: options.term,
                            page: options.page,
                            per_page: this.perPage
                        });
                        if (options.term !== '' &&
                            !this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                            this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                        } else {
                            this.makeRequest(options, autoCompleteUrlParameters);
                        }
                    }
                });
            },

            getChoiceLabel: function() {
                const label = _.result(this.getSelect2Data(), 'label');
                return label !== void 0 ? label : '';
            },

            getServerUpdateData: function() {
                const data = {};
                data[this.valueFieldName] = this.getValue();
                return data;
            },

            getModelUpdateData: function() {
                const data = this.getServerUpdateData();
                data[this.fieldName] = this.getChoiceLabel();
                return data;
            }
        });

    return ProductTaxCodeEditorView;
});
