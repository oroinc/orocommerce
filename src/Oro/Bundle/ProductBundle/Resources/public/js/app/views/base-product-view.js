define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var BaseModel = require('oroui/js/app/models/base/model');
    var $ = require('jquery');
    var _ = require('underscore');

    BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]'
        },

        modelElements: {
            quantity: 'quantity',
            unit: 'unit'
        },

        modelAttr: {
            id: 0,
            quantity: 0,
            unit: ''
        },

        initialize: function(options) {
            BaseProductView.__super__.initialize.apply(this, arguments);

            this.rowId = this.$el.parent().data('row-id');
            this.initModel(options);
            this.initializeElements(options);
            this.initLayout({
                productModel: this.model
            }).done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {},

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }
            if (!this.model && _.isObject(this.collection)) {
                this.model = this.collection.get(this.rowId) || new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            delete this.modelAttr;
            delete this.rowId;
            this.disposeElements();
            BaseProductView.__super__.dispose.apply(this, arguments);
        }
    }));

    return BaseProductView;
});
