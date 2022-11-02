define(function(require) {
    'use strict';

    require('jasmine-jquery');
    require('orofrontend/default/js/app/modules/input-widgets');

    const UnitsUtil = require('oroproduct/js/app/units-util');
    const BaseModel = require('oroui/js/app/models/base/model');
    const _ = require('underscore');
    const $ = require('jquery');
    const Translator = require('orotranslation/lib/translator');

    // fixtures
    const html = require('text-loader!./Fixture/units-select-template.html');

    Translator.fromJSON({
        locale: 'en',
        defaultDomain: 'jsmessages',
        translations: {
            en: {
                jsmessages: {
                    'oro.product.product_unit.item.label.full': 'item',
                    'oro.product.product_unit.set.label.full': 'set',
                    'oro.product.product_unit.kg.label.full': 'kilogram'
                }
            }
        }
    });

    // variables
    let $el;
    let model;

    const getOptions = function($el) {
        return _.map($el.find('option'), function(el) {
            return el.innerHTML;
        });
    };

    describe('oroproduct/js/app/units-util', function() {
        beforeEach(function() {
            window.setFixtures(html);

            $el = $('select');
            model = new BaseModel({
                product_units: {
                    item: 0,
                    set: 0,
                    kg: 3
                }
            });
        });

        afterEach(function() {
            $el = null;
            model = null;
        });

        describe('check default options list', function() {
            it('by default we should see all available options', function() {
                expect(getOptions($el)).toEqual([
                    'Please select...',
                    'each',
                    'hour',
                    'item',
                    'kilogram',
                    'piece',
                    'set'
                ]);
            });
        });

        describe('check option generation', function() {
            it('we should get correct "option" tag', function() {
                expect(UnitsUtil.generateSelectOption('val', 'text')).toEqual('<option value="val">text</option>');
            });
        });

        describe('check get units label', function() {
            it('we should get translated units label by codes', function() {
                expect(UnitsUtil.getUnitsLabel(model)).toEqual({
                    item: 'item',
                    set: 'set',
                    kg: 'kilogram'
                });
            });
        });

        describe('check select update', function() {
            it('we should get limited list of select options with first unit selected', function() {
                UnitsUtil.updateSelect(model, $el);

                expect(getOptions($el)).toEqual(['item', 'set', 'kilogram']);
                expect(model.get('unit')).toEqual('item');
                expect($el.prop('disabled')).toBeFalsy();
                expect($el.prop('readonly')).toBeFalsy();
            });

            it('we should get a placeholder option, once unit_label is defined without unit in model', function() {
                model.set('unit_label', 'piece');
                UnitsUtil.updateSelect(model, $el);

                expect(getOptions($el)).toEqual(['Please select...', 'item', 'set', 'kilogram']);
                expect($el.val()).toEqual('');
                expect($el.prop('disabled')).toBeFalsy();
                expect($el.prop('readonly')).toBeFalsy();
            });

            it('we should get kilogram option selected, once it is defined unit in model', function() {
                model.set('unit', 'kg');
                UnitsUtil.updateSelect(model, $el);

                expect(getOptions($el)).toEqual(['item', 'set', 'kilogram']);
                expect($el.val()).toEqual('kg');
                expect($el.prop('disabled')).toBeFalsy();
                expect($el.prop('readonly')).toBeFalsy();
            });

            it('we should see placeholder if units list is empty', function() {
                model.set('product_units', []);
                model.set('unit', 'kg');
                UnitsUtil.updateSelect(model, $el);

                expect(getOptions($el)).toEqual(['Please select...']);
                expect(model.get('unit')).toEqual('');
                expect($el.prop('disabled')).toBeTruthy();

                model.set('unit_placeholder', '--');
                UnitsUtil.updateSelect(model, $el);

                expect(getOptions($el)).toEqual(['--']);
            });

            it('input widget should be readonly when there is only one option', function() {
                model.set('product_units', {item: 0});
                UnitsUtil.updateSelect(model, $el);

                expect($el.prop('readonly')).toBeTruthy();
            });

            it('input widget should be refreshed', function() {
                $el.val('kg').change();
                $el.inputWidget('create');
                UnitsUtil.updateSelect(model, $el);

                expect($el.inputWidget('data').id).toEqual('kg');
            });
        });
    });
});
