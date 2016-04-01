define(function(require) {
    'use strict';

    var QuoteToOrderWidgetComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');
    var template = require('tpl!../../../templates/quote-to-order-item-error.html');

    if (typeof template === 'string') {
        template = _.template(template);
    }

    QuoteToOrderWidgetComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            $(options._sourceElement).validate(
                {
                    errorClass: 'error',
                    showErrors: function(errorMap, errorList) {
                        var $element = $(this.currentElements[0]);
                        var $container = $element.closest('td');

                        $container.find('[data-role="error-container"]').remove();
                        $element.removeClass(this.settings.errorClass);
                        if (errorList.length) {
                            $element.addClass(this.settings.errorClass);
                            _.each(errorMap, function(message) {
                                $(template({'message': message})).appendTo($container);
                            });
                        }
                    }
                }
            );
        }
    });

    return QuoteToOrderWidgetComponent;
});
