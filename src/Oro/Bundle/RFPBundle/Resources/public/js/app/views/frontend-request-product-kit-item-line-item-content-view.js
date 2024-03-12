import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import NumberFormatter from 'orolocale/js/formatter/number';
import routing from 'routing';

const FrontendRequestProductKitItemLineItemContentView = BaseView.extend(_.extend({}, ElementsHelper, {
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'modelAttr'
    ]),

    viewTemplateSelector: '#rfp-request-product-kit-item-view-template',

    /**
     * @property {Function}
     */
    template: null,

    /**
     * @property {Backbone.Model}
     */
    model: null,

    constructor: function FrontendRequestProductKitItemLineItemContentView(options) {
        FrontendRequestProductKitItemLineItemContentView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductKitItemLineItemContentView.__super__.initialize.call(this, options);

        this.template = _.template($(this.viewTemplateSelector).text());

        this.initModel(options);

        this.render();
    },

    /**
     * @inheritdoc
     */
    render: function() {
        this.$el.html(
            this.template({
                kitItemLineItem: this.model.toJSON(),
                routing: routing,
                numberFormatter: NumberFormatter
            })
        );
    }
}));

export default FrontendRequestProductKitItemLineItemContentView;
