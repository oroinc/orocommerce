define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const routing = require('routing');
    const messenger = require('oroui/js/messenger');
    const widgetManager = require('oroui/js/widget-manager');
    const ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    const BaseTreeManageView = require('oroui/js/app/views/jstree/base-tree-manage-view');

    const WebCatalogTreeView = BaseTreeManageView.extend({
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
        confirmState: true,

        /**
         * @property {String}
         */
        contentNodeUpdateRoute: '',

        /**
         * @property {String}
         */
        contentNodeFormSelector: '',

        /**
         * @inheritdoc
         */
        constructor: function WebCatalogTreeView(options) {
            WebCatalogTreeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WebCatalogTreeView.__super__.initialize.call(this, options);

            this.contentNodeUpdateRoute = options.contentNodeUpdateRoute;
            this.contentNodeFormSelector = options.contentNodeFormSelector;
        },

        onConfirmModalOk: function() {
            this._doMove(this.confirmState);
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
         * @inheritdoc
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            if (data.parent === '#') {
                this.rollback(data);
                messenger.notificationFlashMessage('warning', _.__('oro.webcatalog.jstree.add_new_root_warning'));
                return;
            }

            this.moveEventData = {e: e, data: data};

            if (this.moveEventData.data.old_parent === this.moveEventData.data.parent) {
                this._doMove(false);
                return;
            }

            this._removeConfirmModal();
            this.confirmModal = new ConfirmSlugChangeModal({
                changedSlugs: this._getChangedUrlsList(),
                confirmState: this.confirmState
            })
                .on('ok', this.onConfirmModalOk.bind(this))
                .on('cancel', this.onConfirmModalCancel.bind(this))
                .on('confirm-option-changed', this.onConfirmModalOptionChange.bind(this))
                .open();
        },

        /**
         * @returns {String}
         * @private
         */
        _getChangedUrlsList: function() {
            let list = '';
            const newParentId = this.moveEventData.data.node.parent;
            const nodeId = this.moveEventData.data.node.id;
            const urls = this._getChangedUrls(nodeId, newParentId);
            for (const localization in urls) {
                if (urls.hasOwnProperty(localization)) {
                    const oldSlug = _.macros('oroui::renderDirection')({
                        content: urls[localization].before
                    }).trim();
                    const newSlug = _.macros('oroui::renderDirection')({
                        content: urls[localization].after
                    }).trim();
                    list += '\n' + __(
                        'oro.redirect.confirm_slug_change.changed_slug_item',
                        {
                            old_slug: oldSlug,
                            new_slug: newSlug,
                            purpose: localization
                        }
                    );
                }
            }
            return list;
        },

        _getChangedUrls: function(nodeId, newParentId) {
            let urls;
            $.ajax({
                async: false,
                url: routing.generate('oro_content_node_get_possible_urls', {id: nodeId, newParentId: newParentId}),
                success: result => {
                    urls = result;
                }
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
         * @param {Boolean} createRedirect
         * @private
         */
        _doMove: function(createRedirect) {
            const data = this.moveEventData.data;

            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(this.onMoveRoute),
                data: {
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position,
                    createRedirect: +createRedirect
                },
                success: result => {
                    if (!result.status) {
                        this.rollback(data);
                        messenger.notificationFlashMessage(
                            'error',
                            __('oro.ui.jstree.move_node_error', {nodeText: data.node.text})
                        );
                    } else {
                        this._updateSlugPrototypes(data.node.id, result.slugPrototypes);
                        if (this.reloadWidget) {
                            widgetManager.getWidgetInstanceByAlias(this.reloadWidget, function(widget) {
                                widget.render();
                            });
                        }
                    }
                }
            });
        },

        /**
         * Update Content Node slug prototypes.
         * If currently edited Content Node was moved and it`s slug prototypes were changed they should be updated.
         *
         * @param {Number} nodeId
         * @param {Array} slugPrototypes
         * @private
         */
        _updateSlugPrototypes: function(nodeId, slugPrototypes) {
            const $form = $(this.contentNodeFormSelector);
            const currentUrl = routing.generate(this.contentNodeUpdateRoute, {id: nodeId});

            if ($form.attr('action') === currentUrl) {
                _.each(slugPrototypes, function(slugString, localization) {
                    const $slugStringEl = $form.find(
                        '[name$="[slugPrototypesWithRedirect][slugPrototypes][values][' + localization + ']"]'
                    );
                    if (!$slugStringEl.is(':disabled')) {
                        $slugStringEl.val(slugString);
                    }
                });
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this._removeConfirmModal();

            WebCatalogTreeView.__super__.dispose.call(this);
        }
    });

    return WebCatalogTreeView;
});
