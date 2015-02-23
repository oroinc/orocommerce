/*global define*/
define(function (require) {
    'use strict';

    var SelectDefaultValueComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2battribute/js/app/components/fallback-value-component
     * @extends oroui.app.components.base.Component
     * @class orob2battribute.app.components.FallbackValueComponent
     */
    SelectDefaultValueComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        radio: null,

        /**
         * @property {Object}
         */
        hidden: null,

        /**
         * @property {String}
         */
        class: null,

        /**
         * @property {Object}
         */
        groupContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.radio = options._sourceElement;
            if (!this.radio) {
                return;
            }

            this.class = options['class'];
            if (!this.class) {
                return;
            }

            this.hidden = this.radio.parent().find('input.' + this.class).first();
            if (!this.hidden) {
                return;
            }

            var groupContainer = options['group-container'];
            if (!groupContainer) {
                return;
            }
            this.groupContainer = $(groupContainer);

            // sync radio button with hidden
            if (this.hidden.val()) {
                this.radio.prop('checked', true);
            }

            // sync hidden with radio
            this.radio.click(_.bind(this._onRadioClick, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed || !this.radio || !this.class || !this.hidden || !this.groupContainer) {
                return;
            }

            this.radio.off('.' + this.cid);

            SelectDefaultValueComponent.__super__.dispose.call(this);
        },

        /**
         * Synchronize hidden elements with checked radio button
         *
         * @private
         */
        _onRadioClick: function() {
            var checked = this.radio.prop('checked');
            if (checked) {
                this.groupContainer.find('input.' + this.class).each(function(){
                    $(this).val('');
                });
                this.hidden.val(checked ? '1' : '');
            }

        }
    });

    return SelectDefaultValueComponent;
});
