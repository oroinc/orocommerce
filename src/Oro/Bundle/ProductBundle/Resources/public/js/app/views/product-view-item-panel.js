import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

import 'jquery-ui/effects/effect-fade';

const ProductViewItemPanel = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['target']),

    listen: {
        'layout:content-relocated mediator': 'update'
    },

    constructor: function ProductViewItemPanel(options) {
        ProductViewItemPanel.__super__.constructor.call(this, options);
    },

    update() {
        const relocated = this.$el.find($(this.target));

        if (relocated.get(0)) {
            this.$el.attr('aria-hidden', null);
            this.toggleValidationMessagePlacement(true);
            this.$el.show('fade');
        } else {
            this.$el.attr('aria-hidden', true);
            this.toggleValidationMessagePlacement(false);
            this.$el.hide('fade');
        }
    },

    toggleValidationMessagePlacement(add) {
        const $input = $(this.target).find('input[type="text"]');

        if (add) {
            $input.attr('placement', 'bottom');
        } else {
            $input.attr('placement', null);
        }

        const validator = $input.closest('form').data('validator');
        if (validator) {
            validator.element($input);
        }
    },

    render() {
        this.$el.hide();
        this.$el.addClass('rendered');
        return this;
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$el.removeClass('rendered');
        this.$el.attr('style', null);

        ProductViewItemPanel.__super__.dispose.call(this);
    }
});

export default ProductViewItemPanel;
