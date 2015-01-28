/*global define*/
define(function (require) {
    'use strict';

    var FallbackValueComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2battribute/js/app/components/fallback-value-component
     * @extends oroui.app.components.base.Component
     * @class orob2battribute.app.components.FallbackValueComponent
     */
    FallbackValueComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        element: null,

        /**
         * @property {Object}
         */
        valueContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            var valueContainer = options['value-container'];
            if (!valueContainer) {
                return;
            }
            this.valueContainer = $(valueContainer);

            this._updateValueContainer();
            this.element.change(_.bind(this._updateValueContainer, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed || !this.element || !this.valueContainer) {
                return;
            }

            this.element.off('.' + this.cid);

            FallbackValueComponent.__super__.dispose.call(this);
        },

        /**
         * Enable/disable fields according to fallback selector value
         *
         * @private
         */
        _updateValueContainer: function() {
            var isEnabled = this.element.val() == '';

            this.valueContainer.find(':input').each(function() {
                if (isEnabled) {
                    $(this).removeAttr('disabled');
                } else {
                    $(this).attr('disabled','disabled');
                }
            });
        }
    });

    return FallbackValueComponent;
});
