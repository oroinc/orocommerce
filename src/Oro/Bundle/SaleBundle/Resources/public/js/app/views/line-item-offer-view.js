import $ from 'jquery';
import _ from 'underscore';
import LineItemProductView from 'oroproduct/js/app/views/line-item-product-view';
import mediator from 'oroui/js/mediator';
import UnitsUtil from 'oroproduct/js/app/units-util';

const LineItemOfferView = LineItemProductView.extend({
    /**
     * @property {Object}
     */
    options: {
        fullName: '',
        allUnits: [],
        $: {
            product: ''
        }
    },

    modelAttr: _.extend({}, LineItemProductView.prototype.modelAttr, {
        checksum: ''
    }),

    modelEvents: _.extend({}, LineItemProductView.prototype.modelEvents, {
        'product_units onProductUnitsChange': ['change', 'onProductUnitsChange']
    }),

    /**
     * @inheritdoc
     */
    constructor: function LineItemOfferView(options) {
        LineItemOfferView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.elements.id = $(options.$.product);
        this.options = $.extend(true, {}, this.options, options || {});
        _.each(this.options.$, (selector, field) => {
            this.options.$[field] = $(selector);
        });

        mediator.trigger('entry-point:listeners:off');

        this.deferredInitializeCheck(options, ['lineItemModel']);
    },

    deferredInitialize: function(options) {
        this.lineItemModel = options.lineItemModel;
        this.lineItemModel.on('change', this.updateModel, this);
        this.updateModel();

        LineItemOfferView.__super__.initialize.call(this, options);

        this.onProductUnitsChange();

        // get all units
        _.each(this.getElement('unit').find('option'), elem => {
            this.options.allUnits.push({code: elem.value, label: elem.text});
        });
        this.model.on('product:unit:filter-values', this.filterUnits.bind(this));

        this.entryPointTriggers([
            this.options.$.quantity,
            this.options.$.productUnit,
            this.options.$.currency,
            this.options.$.priceValue,
            this.options.$.priceType
        ]);

        this.listenTo(mediator, {
            'entry-point:quote:load:response': this.onQuoteEntryPointLoad.bind(this)
        });

        mediator.trigger('entry-point:listeners:on');
    },

    updateModel: function() {
        if (this.model) {
            this.model.set({
                id: this.lineItemModel.get('productId'),
                product_units: this.lineItemModel.get('product_units') || {}
            });
        } else {
            this.modelAttr.id = this.lineItemModel.get('productId');
            this.modelAttr.product_units = this.lineItemModel.get('product_units') || {};
        }
    },

    onQuoteEntryPointLoad: function(response) {
        this.model.set('checksum', response.checksum ? response.checksum[this.options.fullName] || '' : '');
    },

    onProductUnitsChange: function() {
        UnitsUtil.updateSelect(this.model, this.getElement('unit'), true);
        LineItemOfferView.__super__.onProductUnitsChange.call(this);
    },

    /**
     * @param {Array} units
     */
    filterUnits: function(units) {
        const $select = this.getElement('unit');
        const value = $select.val();

        let isChanged = false;
        if (units) {
            _.each(this.options.allUnits, unit => {
                if (-1 !== $.inArray(unit.code, units)) {
                    if (!$select.find('[value="' + unit.code + '"]').length) {
                        $select.append($('<option/>').val(unit.code).text(unit.label));
                        isChanged = true;
                    }
                } else if ($select.find('[value="' + unit.code + '"]').length) {
                    $select.find('[value="' + unit.code + '"]').remove();
                    isChanged = true;
                }
            });

            if (isChanged) {
                $select.val(value);
                if ($select.val() === null) {
                    $select
                        .val(units[0])
                        .trigger('value:changed')
                        .trigger('change');
                }
            }
        } else if (value) {
            $select
                .val(null)
                .trigger('value:changed')
                .trigger('change');
        }
    },

    /**
     * @param {jQuery|Array} fields
     */
    entryPointTriggers: function(fields) {
        _.each(fields, function(fields) {
            _.each(fields, function(field) {
                $(field).attr('data-entry-point-trigger', true);
            });
        });
    }
});

export default LineItemOfferView;
