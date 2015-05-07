/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var PasswordGenerateComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery');

    PasswordGenerateComponent = BaseComponent.extend({
        initialize: function (options) {
            this.$el = $('.' + options.class);
            this.passwordInput = $('.' + options.targetClass + ' input');
            console.log(this.passwordInput);
            this.togglePassword();

            this.$el.click(_.bind(this.togglePassword, this));
        },

        togglePassword: function()
        {
            if (this.$el.is(':checked')) {
                this.passwordInput.attr('disabled', true);
            } else {
                this.passwordInput.attr('disabled', false);
            }
        }
    });

    return PasswordGenerateComponent;
});