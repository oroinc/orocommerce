define(function(require) {
    'use strict';

    var WebCatalogTreeComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');
    var widgetManager = require('oroui/js/widget-manager');
    var ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    var BasicTreeManageComponent = require('oroui/js/app/components/basic-tree-manage-component');

    WebCatalogTreeComponent = BasicTreeManageComponent.extend({

        /**
         * @property {Object}
         */
        moveEventData: null,

        /**
         * @property {Object}
         */
        confirmModal: null,

        /**
         * @property {Boolean}
         */
        checkboxEnabled: true,

        /**
         * @property {Boolean}
         */
        confirmState: true,

        onConfirmModalOk: function() {
            var data = this.moveEventData.data;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(this.onMoveRoute),
                data: {
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position,
                    createRedirect: +this.confirmState
                },
                success: _.bind(function(result) {
                    if (!result.status) {
                        this.rollback(data);
                        messenger.notificationFlashMessage(
                            'error',
                            __('oro.ui.jstree.move_node_error', {nodeText: data.node.text})
                        );
                    } else if (this.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(this.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                }, this)
            });
        },

        onConfirmModalCancel: function() {
            this.rollback(this.moveEventData.data);
        },

        /**
         * @param {Boolean} confirmState
         */
        onConfirmModalOptionChange: function(confirmState) {
            this.confirmState = confirmState;
        },

        /**
         * @inheritDoc
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            this.moveEventData = {e: e, data: data};

            this._removeConfirmModal();
            this.confirmModal = new ConfirmSlugChangeModal({
                'changedSlugs': this._getChangedUrlsList(),
                'confirmState': this.confirmState
            })
                .on('ok', _.bind(this.onConfirmModalOk, this))
                .on('cancel', _.bind(this.onConfirmModalCancel, this))
                .on('confirm-option-changed', _.bind(this.onConfirmModalOptionChange, this))
                .open();
        },

        /**
         * @returns {String}
         * @private
         */
        _getChangedUrlsList: function() {
            var list = '';
            var newParentId = this.moveEventData.data.node.parent;
            var nodeId = this.moveEventData.data.node.id;
            var urls = this._getChangedUrls(nodeId, newParentId);
            for (var localization in urls) {
                if (urls.hasOwnProperty(localization)) {
                    list += '\n' + __(
                            'oro.redirect.confirm_slug_change.changed_slug_item',
                            {
                                'old_slug': urls[localization].before,
                                'new_slug': urls[localization].after,
                                'purpose': localization
                            }
                        );
                }
            }
            return list;
        },

        _getChangedUrls: function(nodeId, newParentId) {
            var urls;
            $.ajax({
                async: false,
                url: routing.generate('oro_content_node_get_possible_urls', {id: nodeId, newParentId: newParentId}),
                success: _.bind(function(result) {
                    urls = result;
                }, this)
            });

            if (typeof urls !== 'undefined') {
                return urls;
            } else {
                messenger.notificationFlashMessage(
                    'error',
                    __('oro.ui.unexpected_error')
                );
                throw new TypeError('Can\'t get changed urls.');
            }
        },

        /**
         * @private
         */
        _removeConfirmModal: function() {
            if (this.confirmModal) {
                this.confirmModal.off();
                this.confirmModal.dispose();
                delete this.confirmModal;
                this.confirmState = true;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this._removeConfirmModal();

            WebCatalogTreeComponent.__super__.dispose.call(this);
        }
    });

    return WebCatalogTreeComponent;
});
