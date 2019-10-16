define(function(require) {
    'use strict';

    var GrapesJSModules;
    var StyleManagerModule = require('orocms/js/app/grapesjs/modules/style-manager-module');
    var PanelManagerModule = require('orocms/js/app/grapesjs/modules/panels-module');
    var DevicesModule = require('orocms/js/app/grapesjs/modules/devices-module');
    var StyleIsolationModule = require('orocms/js/app/grapesjs/modules/style-isolation-module');
    var _ = require('underscore');

    /**
     * Create GrapesJS module manager
     * @type {*|void}
     */
    GrapesJSModules = _.extend({
        /**
         * Module namespace
         * @property {String}
         */
        namespace: '-module',

        /**
         * Call module method
         * @param name
         * @param options
         */
        call: function(name, options) {
            if (!this[name + this.namespace] || !_.isFunction(this[name + this.namespace])) {
                return;
            }

            return new this[name + this.namespace](options);
        },

        /**
         * Get module by name
         * @param name
         * @returns {*}
         */
        getModule: function(name) {
            if (!this[name + this.namespace]) {
                return;
            }
            return this[name + this.namespace];
        }
    }, {
        'style-manager-module': StyleManagerModule,
        'panel-manager-module': PanelManagerModule,
        'devices-module': DevicesModule,
        'style-isolation-module': StyleIsolationModule
    });

    return GrapesJSModules;
});
