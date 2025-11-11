import BaseComponent from 'oroui/js/app/components/base/component';
import _ from 'underscore';

const ProductImageTypeRadioControlComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {},

    /**
     * @inheritdoc
     */
    constructor: function ProductImageTypeRadioControlComponent(options) {
        ProductImageTypeRadioControlComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);

        const form = this.options._sourceElement.closest('form');
        const allRadiosWithImageTypeSelector = 'input[type=radio][data-image-type]:checked';

        form.on('change', allRadiosWithImageTypeSelector, function() {
            const currentType = this.dataset.imageType;
            const withCurrentTypeSelector = 'input[type=radio][data-image-type="' + currentType + '"]:checked';

            form.find(withCurrentTypeSelector).not(this).prop('checked', false);
        });
    }
});

export default ProductImageTypeRadioControlComponent;
