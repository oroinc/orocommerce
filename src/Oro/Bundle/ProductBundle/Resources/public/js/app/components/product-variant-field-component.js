define(function(require) {
    'use strict';

    var ProductVariantFieldComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var ViewComponent = require('oroui/js/app/components/view-component');
    var error = require('oroui/js/error');

    ProductVariantFieldComponent = ViewComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            simpleProductVariants: []
        },

        /**
         * Source DOM element
         */
        $el: null,

        /**
         * All options without default disabled
         * @property {Array}
         */
        filteredOptions: [],

        /**
         * Filtered options after resolving
         */
        _filtered: null,

        /**
         * Hierarchy stack of product variants
         */
        _hierarchy: [],

        /**
         * Current state
         */
        state: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            ProductVariantFieldComponent.__super__.initialize.apply(this, arguments);

            this.$el = this.options._sourceElement;

            this._prepareProductVariants();

            this.deferredInit.done(_.bind(this._initVariantInstances, this));
        },

        /**
         * Set state
         * @param name
         * @param value
         * @returns {*}
         */
        setState: function(name, value) {

            if (_.isUndefined(value)) {
                return error.showErrorInConsole('The value should be defined');
            }

            if (!this.state) {
                this.state = {};
            }

            this.state[name] = value;

            return this.state;
        },

        /**
         * Get current state by property
         *
         * @param {String} name
         * @returns {null}
         */
        getState: function(name) {
            return name ? this.state[name] : this.state;
        },

        /**
         * Trigger select2 to update view
         */
        updateFields: function() {
            this.$el.find('select').each(_.bind(function(index, select) {
                if (this._filtered.indexOf($(select).val()) === -1) {
                    $(select).val('');
                    this.setState(this._extractName($(select).data('name')), null);
                }
                $(select).trigger('change.select2');
            }, this));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            delete this._filtered;
            delete this._hierarchy;
            delete this.state;
            delete this.filteredOptions;

            ProductVariantFieldComponent.__super__.dispose.apply(this);
        },

        /**
         * Initialize variants
         *
         * @private
         */
        _initVariantInstances: function() {
            var onChangeHandler = _.bind(this._onVariantFieldChange, this, this.options.simpleProductVariants);

            if (this.$el.find('select').length) {
                this.$el.find('select').each(_.bind(function(index, select) {
                    var $select = $(select);
                    var normalizeName = this._extractName($select.data('name'));

                    this.filteredOptions = this.filteredOptions.concat(
                        $select.find('option').get().filter(function(option) {
                            option.value = option.value !== '' ? normalizeName + '_' + option.value : '';
                            return !option.disabled && option.value !== '';
                        })
                    );

                    this._appendToHierarchy(normalizeName);
                    this.setState(normalizeName, $select.val());

                }, this));
            }

            this._resolveVariantFieldsChain(this.options.simpleProductVariants);
            this.$el.on('change', 'select', onChangeHandler);
        },

        /**
         * Fix case where attributes similar value
         * make attribute unique value
         * @private
         */
        _prepareProductVariants: function() {
            _.each(this.options.simpleProductVariants, function(variant) {
                _.each(variant, function(attr, key, list) {
                    list[key] = key + '_' + attr;
                });
            });
        },

        /**
         * Append new item to hierarchy stack
         * @param newOne
         * @returns {null}
         * @private
         */
        _appendToHierarchy: function(newOne) {
            if (!_.isString(newOne)) {
                return error.showErrorInConsole(newOne + ' should be string');
            }

            if (this._hierarchy.indexOf(newOne) === -1) {
                this._hierarchy.push(newOne);
            } else {
                return error.showErrorInConsole('Item: ' + newOne + ' is already exist!');
            }

            return this._hierarchy;
        },

        /**
         * onChange handler for select variant fields
         * @param simpleProductVariants
         * @param event
         * @private
         */
        _onVariantFieldChange: function(simpleProductVariants, event) {
            var $target = $(event.target);

            this.setState(this._extractName($target.data('name')), $target.val());
            this._resolveVariantFieldsChain(simpleProductVariants);
        },

        /**
         * Resolve field hierarchy depends selected options
         * @param {Object} simpleProductVariants
         * @private
         */
        _resolveVariantFieldsChain: function(simpleProductVariants) {
            this._filtered = this._resolveHierarchy(simpleProductVariants);

            this.filteredOptions.forEach(_.bind(function(field) {
                field.disabled = _.indexOf(this._filtered, field.value) === -1;
            }, this));

            this._updateProduct();
            this.updateFields();
        },

        /**
         *
         * @param {Object} simpleProductVariants
         * @returns {Array}
         * @private
         */
        _resolveHierarchy: function(simpleProductVariants) {
            var result = [];

            this._hierarchy.forEach(_.bind(function(field, index) {
                var parentField = this._hierarchy[index - 1];

                simpleProductVariants = _.isUndefined(parentField) ?
                    simpleProductVariants :
                    _.where(simpleProductVariants, this._prepareFoundKeyValue(parentField));

                result = result.concat(
                    _.uniq(
                        _.pluck(simpleProductVariants, field)
                    )
                );
            }, this));

            return result;
        },

        _prepareFoundKeyValue: function(value) {
            var result = {};
            result[value] = this.getState(value);

            return result;
        },

        /**
         * Update product model in view
         *
         * @private
         */
        _updateProduct: function() {
            var variants = this.options.simpleProductVariants;
            this.foundProductId = null;

            for (var variant in variants) {
                if (variants.hasOwnProperty(variant) && _.isEqual(this.getState(), variants[variant])) {
                    this.foundProductId = variant;
                    break;
                }
            }

            this.view.updateProductInfo(this.foundProductId);
        },

        _prefixArray: function(arr, prefix) {
            return arr.map(function(a) {
                return prefix + '_' + a;
            });
        },

        /**
         * Helper method for normalize field name from 'form__name' to 'Name'
         *
         * @param {String} name
         * @returns {string}
         * @private
         */
        _extractName: function(name) {
            name = name.split('__').slice(-1)[0];
            name = name.split('-').reduce(function(str, n) {
                str += n[0].toUpperCase() + n.slice(1);
                return str;
            }, '');
            return name;
        }
    });

    return ProductVariantFieldComponent;
});
