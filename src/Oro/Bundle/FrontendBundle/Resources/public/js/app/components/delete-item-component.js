/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DeleteItemComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DeleteConfirmation = require('orofrontend/js/app/components/delete-confirmation');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    DeleteItemComponent = BaseComponent.extend({
        initialize: function(options) {
            this.$elem = options._sourceElement;
            this.url = options.url || routing.generate(options.route, options.routeParams || {});
            this.removeClass = options.removeClass;
            this.redirect = options.redirect;
            this.confirmMessage = options.confirmMessage;
            this.sucsessMessage = options.sucsessMessage || __('item_deleted');
            this.okButtonClass = options.okButtonClass;
            this.cancelButtonClass = options.cancelButtonClass;
            this.triggerData = options.triggerData || null;

            this.$elem.on('click', _.bind(this.deleteItem, this));
        },

        deleteItem: function() {
            if (this.confirmMessage) {
                this.deleteWithConfirmation();
            } else {
                this.deleteWithoutConfirmation();
            }
        },

        deleteWithConfirmation: function() {
            var options = {
                content: this.confirmMessage
            };

            if (this.okButtonClass) {
                options = _.extend(options, {'okButtonClass' : this.okButtonClass})
            }

            if (this.cancelButtonClass) {
                options = _.extend(options, {'cancelButtonClass' : this.cancelButtonClass})
            }

            var confirm = new DeleteConfirmation(options);
            confirm.on('ok',_.bind(this.deleteWithoutConfirmation, this));
            confirm.open();
        },

        deleteWithoutConfirmation: function(e) {
            var self = this;
            $.ajax({
                url: self.url,
                type: 'DELETE',
                success: function() {
                    if (self.removeClass) {
                        self.$elem.closest('.' + self.removeClass).remove();
                    }

                    if (self.redirect) {
                        self.deleteWithRedirect(e);
                    } else {
                        self.deleteWithoutRedirect(e);
                    }
                },
                error: function() {
                    var message = __('unexpected_error');
                    mediator.execute('hideLoading');
                    mediator.execute('showMessage', 'error', message);
                }
            })
        },

        deleteWithRedirect: function(e) {
            mediator.execute('showFlashMessage', 'success', this.sucsessMessage);
            mediator.execute('redirectTo', {url: this.redirect}, {redirect: true});
        },

        deleteWithoutRedirect: function(e) {
            mediator.execute('showMessage', 'success', this.sucsessMessage, {'flash': true});
            mediator.trigger('frontend:item:delete', this.triggerData || e);
        }
    });

    return DeleteItemComponent;
});
