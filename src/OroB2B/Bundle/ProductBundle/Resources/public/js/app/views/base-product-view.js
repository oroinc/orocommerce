define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var BaseModel = require('oroui/js/app/models/base/model');
    var $ = require('jquery');
    var _ = require('underscore');

    BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]'
        },

        modelElements: ['quantity', 'unit'],

        modelAttr: {
            id: 0,
            quantity: 0,
            unit: ''
        },

        initialize: function(options) {
            BaseProductView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            this.initializeElements(options);
            this.initLayout({
                productModel: this.model
            });
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }
            if (!this.model) {
                this.model = new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            delete this.modelAttr;
            this.disposeElements();
            BaseProductView.__super__.dispose.apply(this, arguments);
        }
    }));

    return BaseProductView;
});
