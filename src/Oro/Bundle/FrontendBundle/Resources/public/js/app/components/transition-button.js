define(function(require) {
    'use strict';

    var TransitionButton;
    var ButtonComponent = require('oroworkflow/js/app/components/button-component');

    TransitionButton = ButtonComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            template: require('text!orofrontend/templates/transition-confirmation.html'),
            allowCancel: true,
            allowOk: true,
            cancelButtonClass: 'btn cancel',
            okButtonClass: 'btn btn--info ok'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options._sourceElement.attr('data-transition-confirmation-options', JSON.stringify(this.options));
            TransitionButton.__super__.initialize.apply(this, arguments);
        }
    });

    return TransitionButton;
});
