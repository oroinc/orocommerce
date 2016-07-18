/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DeleteItemComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DeleteConfirmation = require('orob2bfrontend/js/app/components/delete-confirmation');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    DeleteItemComponent = BaseComponent.extend({
        confirmRemoveComponent: DeleteConfirmation,

        initialize: function(options) {
            this.$elem = options._sourceElement;
            this.url = options.url;
            this.removeClass = options.removeClass;
            this.redirect = options.redirect;
            this.hasOwnTrigger = options.hasOwnTrigger;
            this.lineItemId = options.lineItemId;
            this.confirmMessage = options.confirmMessage;
            this.sucsessMessage = options.sucsessMessage || __('item_deleted');
            this.okButtonClass = options.okButtonClass;
            this.cancelButtonClass = options.cancelButtonClass;

            if (!this.hasOwnTrigger) {
                this.$elem.on('click', _.bind(this.deleteItem, this));
            }
        },

        dispose: function() {
            if (!this.hasOwnTrigger) {
                this.$elem.off('click', _.bind(this.deleteItem, this));
            }
            delete this.confirmRemoveComponent;
            DeleteItemComponent.__super__.dispose.apply(this, arguments);
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

            var confirmRemove = new this.confirmRemoveComponent(options);
            confirmRemove.on('ok',_.bind(this.deleteWithoutConfirmation, this))
                .open();
        },
        deleteWithoutConfirmation: function(e) {
            var self = this;
            $.ajax({
                url: self.url,
                type: 'DELETE',
                success: function() {
                    self.$elem.closest('.' + self.removeClass).remove();

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
            mediator.trigger('frontend:item:delete', {lineItemId: this.lineItemId});
            mediator.execute('showMessage', 'success', this.sucsessMessage, {'flash': true});
        }
    });

    return DeleteItemComponent;
});
