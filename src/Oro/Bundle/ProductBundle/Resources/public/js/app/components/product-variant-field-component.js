define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const ViewComponent = require('oroui/js/app/components/view-component');
    const error = require('oroui/js/error');
    const tools = require('oroui/js/tools');

    const ProductVariantFieldComponent = ViewComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            simpleProductVariants: {}
        },

        /**
         * Source DOM element
         */
        $el: null,

        /**
         * All options without default disabled
         * @property {Array}
         */
        filteredOptions: null,

        /**
         * Filtered options after resolving
         * @property {Array}
         */
        _filtered: null,

        /**
         * Hierarchy stack of product variants
         * @property {Array}
         */
        _hierarchy: null,

        /**
         * Current state
         * @property {Object}
         */
        state: null,

        /** @property {Object} */
        simpleProductVariants: {},

        /**
         * @inheritdoc
         */
        constructor: function ProductVariantFieldComponent(options) {
            ProductVariantFieldComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.filteredOptions = [];
            this._hierarchy = [];
            this.state = {};

            this.options = _.defaults(options || {}, tools.deepClone(this.options));
            ProductVariantFieldComponent.__super__.initialize.call(this, options);

            // _sourceElement is a form element which contains selects
            this.$el = this.options._sourceElement;

            this._prepareProductVariants();

            this.deferredInit.done(_.bind(this._initVariantInstances, this));
        },

        /**
         * Set state
         *
         * @param {string} name
         * @param {object} value
         * @returns {*}
         */
        setState: function(name, value) {
            if (_.isUndefined(value)) {
                return error.showErrorInConsole('The value should be defined');
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

            ProductVariantFieldComponent.__super__.dispose.call(this);
        },

        /**
         * Initialize variants
         *
         * @private
         */
        _initVariantInstances: function() {
            const onChangeHandler = _.bind(this._onVariantFieldChange, this, this.simpleProductVariants);

            if (this.$el.find('select').length) {
                this.$el.find('select').each(_.bind(function(index, select) {
                    const $select = $(select);
                    const normalizeName = this._extractName($select.data('name'));

                    this.filteredOptions = this.filteredOptions.concat(
                        $select.find('option').get().filter(_.bind(function(option) {
                            option.value = option.value !== '' ? normalizeName + '_' + option.value : '';
                            return !option.disabled && option.value !== '';
                        }, this))
                    );

                    this._appendToHierarchy(normalizeName);
                    this.setState(normalizeName, $select.val());
                }, this));
            }

            this._resolveVariantFieldsChain(this.simpleProductVariants);
            this.$el.on('change', 'select', onChangeHandler);
        },

        /**
         * Fix case where attributes similar value make attribute unique value
         *
         * @private
         */
        _prepareProductVariants: function() {
            this.simpleProductVariants = _.mapObject(this.options.simpleProductVariants, function(variant) {
                return _.reduce(variant.attributes, function(memo, attr, key) {
                    memo[this._extractName(key)] = (
                        this._extractName(key) + '_' + this._normalizeBool(attr)
                    );
                    return memo;
                }, {}, this);
            }, this);
        },

        /**
         * Append new item to hierarchy stack
         *
         * @param {object} newOne
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
         *
         * @param {object} simpleProductVariants
         * @param {object} event
         * @private
         */
        _onVariantFieldChange: function(simpleProductVariants, event) {
            const $target = $(event.target);

            this.setState(this._extractName($target.data('name')), $target.val());
            this._resolveVariantFieldsChain(simpleProductVariants);
        },

        /**
         * Resolve field hierarchy depends selected options
         *
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
            let result = [];

            this._hierarchy.forEach(_.bind(function(field, index) {
                const parentField = this._hierarchy[index - 1];

                simpleProductVariants = _.isUndefined(parentField)
                    ? simpleProductVariants
                    : _.where(simpleProductVariants, this._prepareFoundKeyValue(parentField));

                result = result.concat(_.uniq(_.pluck(simpleProductVariants, field)));
            }, this));

            return result;
        },

        _prepareFoundKeyValue: function(value) {
            const result = {};
            result[value] = this.getState(value);

            return result;
        },

        /**
         * Update product model in view
         *
         * @private
         */
        _updateProduct: function() {
            const variants = this.simpleProductVariants;

            for (const variant in variants) {
                if (variants.hasOwnProperty(variant) && _.isEqual(this.getState(), variants[variant])) {
                    const {attributes, ...data} = this.options.simpleProductVariants[variant];

                    this.view.updateProductModel({
                        id: variant,
                        ...data
                    });
                    break;
                }
            }
        },

        /**
         * Helper method for normalize field name from 'form__name' to 'Name'
         *
         * @param {String} name
         * @returns {string}
         * @private
         */
        _extractName: function(name) {
            name = name.toLowerCase().split('__').slice(-1)[0];
            name = name.replace(/[-_]/g, '');
            return name;
        },

        /**
         * Convert from "true" or "false" to "1" and "0"
         *
         * @param {boolean} value
         * @returns {number}
         * @private
         */
        _normalizeBool: function(value) {
            return _.isBoolean(value) ? +value : value;
        }
    });

    return ProductVariantFieldComponent;
});
