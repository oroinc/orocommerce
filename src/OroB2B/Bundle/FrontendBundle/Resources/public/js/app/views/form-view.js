/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var FormView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    FormView = BaseView.extend({
        options: {
            selectors: {}
        },

        fields: {},

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            _.each(this.options.selectors, function (selector, key) {
                this.fields[key] = this.options.el.find(selector);
            }, this);

            this._setChangeListeners();
        },

        _setChangeListeners: function () {
            _.each(this.fields, function ($field, key) {
                $field.on('change', function () {
                    mediator.trigger('update:' + key, $field.val());
                });
            })
        },



        dispose: function () {
            if (this.disposed) {
                return;
            }

            _.each(this.fields, function ($field) {
                $field.off();
            });

            FormView.__super__.dispose.call(this);
        }
    });

    return FormView;
});