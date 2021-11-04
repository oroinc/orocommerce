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
                const _this = this;
                const options = _.omit(ProductTaxCodeEditorView.__super__.getSelect2Options.call(this), 'data');

                return _.extend(options, {
                    allowClear: true,
                    noFocus: true,
                    formatSelection: function(item) {
                        return item.label;
                    },
                    formatResult: function(item) {
                        return item.label;
                    },
                    initSelection: function(element, callback) {
                        callback(_this.getInitialResultItem());
                    },
                    query: function(options) {
                        _this.currentTerm = options.term;
                        if (_this.currentRequest && _this.currentRequest.term !== '' &&
                            _this.currentRequest.state() !== 'resolved') {
                            _this.currentRequest.abort();
                        }
                        const autoCompleteUrlParameters = _.extend(_this.model.toJSON(), {
                            term: options.term,
                            page: options.page,
                            per_page: _this.perPage
                        });
                        if (options.term !== '' &&
                            !_this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                            _this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                        } else {
                            _this.makeRequest(options, autoCompleteUrlParameters);
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
