define(function(require) {
    'use strict';

    var _ = require('underscore');

    /**
     * Create panel manager instance
     * @param options
     * @constructor
     */
    var DevicesModule = function(options) {
        _.extend(this, _.pick(options, ['builder']));

        this.init();
    };

    DevicesModule.prototype = {
        /**
         * @property {DOM.Element}
         */
        $builderIframe: null,

        /**
         * @property {Object}
         */
        breakpoints: {},

        /**
         * @property {DOM.Element}
         */
        canvasEl: null,

        /**
         * Run device manager
         */
        init: function() {
            this.$builderIframe = this.builder.Canvas.getFrameEl();
            this.canvasEl = this.builder.Canvas.getElement();

            _.delay(_.bind(this._getCSSBreakpoint, this), 300);

            this.builder.on('changeTheme', _.debounce(_.bind(this._getCSSBreakpoint, this), 300));
        },

        /**
         * Fetch brakpoints from theme stylesheet
         * @private
         */
        _getCSSBreakpoint: function() {
        }
    };

    return DevicesModule;
});
