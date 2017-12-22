define(function(require) {
    'use strict';

    var _ = require('underscore');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    var typeMap = {
        'string': 'string',
        'text': 'string',
        'boolean': 'boolean',
        'enum': 'enum',
        'integer': 'integer',
        'float': 'float',
        'money': 'float',
        'double': 'float',
        'datetime': 'datetime',
        'date': 'date',
        'manyToOne': 'relation',
        'ref-one': 'relation'
    };

    var pricerule = {
        optionsFilter: {
            unidirectional: false,
            exclude: false
        },
        fieldsFilterWhitelist: {
            'Oro\\Bundle\\PricingBundle\\Entity\\PriceList': {
                'prices': true
            }
        },
        // included only type from map object
        include: _.keys(typeMap)
            .map(function(type) {
                return {type: type};
            })
    };

    EntityStructureDataProvider.defineFilterPreset('pricerule', pricerule);

    EntityStructureDataProvider.defineFilterPreset('priceruleNumeric', {
        optionsFilter: _.defaults({identifier: false}, pricerule.optionsFilter),
        fieldsFilterWhitelist: pricerule.fieldsFilterWhitelist,
        // included only numeric and relation types from map object
        include: pricerule.include
            .filter(function(item) {
                return _.contains(['integer', 'float', 'relation'], typeMap[item.type]);
            })
    });

    EntityStructureDataProvider.defineFilterPreset('priceruleCurrency', _.defaults({
        fieldsFilterWhitelist: _.extend({
            'Oro\\Bundle\\ProductBundle\\Entity\\Product': {
                'map': true,
                'msrp': true
            }
        }, pricerule.fieldsFilterWhitelist),
        fieldsFilterer: function(entityClassName, fields) {
            return fields.filter(function(field) {
                return this._isWhitelistedField(entityClassName, field) ||
                    (typeMap[field.type] === 'string' && field.name.indexOf('currency') !== -1);
            }.bind(this));
        }
    }, pricerule));

    EntityStructureDataProvider.defineFilterPreset('priceruleUnit', {
        fieldsFilterWhitelist: {
            'Oro\\Bundle\\ProductBundle\\Entity\\Product': {
                'map': true,
                'msrp': true,
                'primaryUnitPrecision': true
            },
            'Oro\\Bundle\\PricingBundle\\Entity\\PriceAttributeProductPrice': {
                'unit': true
            },
            'Oro\\Bundle\\ProductBundle\\Entity\\ProductUnitPrecision': {
                'unit': true
            },
            'Oro\\Bundle\\PricingBundle\\Entity\\PriceList': {
                'prices': true
            },
            'Oro\\Bundle\\PricingBundle\\Entity\\ProductPrice': {
                'unit': true
            }
        },
        fieldsFilterer: function(entityClassName, fields) {
            return fields.filter(function(field) {
                return this._isWhitelistedField(entityClassName, field);
            }.bind(this));
        }
    });
});
