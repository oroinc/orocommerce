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
            this.$el.removeAttr('aria-hidden');
            this.toggleValidationMessagePlacement(true);
            this.toggleDropdownPlacement(false);
            this.$el.show('fade');
        } else {
            this.$el.attr('aria-hidden', true);
            this.toggleValidationMessagePlacement(false);
            this.toggleDropdownPlacement(true);
            this.$el.hide('fade');
        }
    },

    toggleValidationMessagePlacement(add) {
        const $input = $(this.target).find('input[type="text"]');

        if (add) {
            $input.attr('placement', 'bottom');
        } else {
            $input.removeAttr('placement');
        }

        const validator = $input.closest('form').data('validator');
        if (validator) {
            validator.element($input);
        }
    },

    toggleDropdownPlacement(flip) {
        const $dropdownButton = $(this.target).find('.dropdown-toggle[data-toggle="dropdown"]');

        $dropdownButton.attr('data-flip', 'false');
        $dropdownButton.dropdown('update');

        if (flip) {
            $dropdownButton.removeAttr('data-flip');
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
        this.$el.removeAttr('style');

        ProductViewItemPanel.__super__.dispose.call(this);
    }
});

export default ProductViewItemPanel;
