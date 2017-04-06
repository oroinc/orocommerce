define(function(require) {
    'use strict';

    var ProductTaxCodeEditorView;
    var AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    var _ = require('underscore');
    require('jquery.select2');

    ProductTaxCodeEditorView =
        AbstractRelationEditorView.extend(/** @exports ProductTaxCodeEditorView.prototype */{
            initialize: function(options) {
                ProductTaxCodeEditorView.__super__.initialize.apply(this, arguments);
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
                var _this = this;
                var options = _.omit(ProductTaxCodeEditorView.__super__.getSelect2Options.call(this), 'data');

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
                        var autoCompleteUrlParameters = _.extend(_this.model.toJSON(), {
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
                var label = _.result(this.getSelect2Data(), 'label');
                return label !== void 0 ? label : '';
            },

            getServerUpdateData: function() {
                var data = {};
                data[this.valueFieldName] = this.getValue();
                return data;
            },

            getModelUpdateData: function() {
                var data = this.getServerUpdateData();
                data[this.fieldName] = this.getChoiceLabel();
                return data;
            }
        });

    return ProductTaxCodeEditorView;
});
