define(function(require) {
    'use strict';

    const CellPopupEditorComponent = require('orodatagrid/js/app/components/cell-popup-editor-component');
    const ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const _ = require('underscore');
    const $ = require('jquery');

    const ProductNamePopupEditorComponent =
        CellPopupEditorComponent.extend(/** @exports ProductNamePopupEditorComponent.prototype */{
            /**
             * @property {Object}
             */
            options: {
                changedSlugsUrl: 'oro_product_get_changed_default_slug'
            },

            /**
             * @property {Boolean}
             */
            createRedirectOption: true,

            /**
             * @property {Boolean}
             */
            confirmModalOpened: false,

            /**
             * @inheritdoc
             */
            constructor: function ProductNamePopupEditorComponent(options) {
                ProductNamePopupEditorComponent.__super__.constructor.call(this, options);
            },

            /**
             * @inheritdoc
             */
            initialize: function(options) {
                this.options = _.defaults(options || {}, this.options);

                return ProductNamePopupEditorComponent.__super__.initialize.call(this, options);
            },

            /**
             * @inheritdoc
             */
            saveCurrentCell: function() {
                if (!this.view.isChanged()) {
                    this.exitEditMode(true);
                    return true;
                }
                if (!this.view.isValid()) {
                    return false;
                }

                if (this.confirmModalOpened) {
                    return true;
                } else {
                    this.confirmModalOpened = true;
                }

                let urls = {};

                mediator.execute('showLoading');
                $.ajax({
                    url: routing.generate(this.options.changedSlugsUrl, {id: this.view.model.id}),
                    type: 'POST',
                    data: {productName: this.view.getValue()},
                    dataType: 'json',
                    success: response => {
                        mediator.execute('hideLoading');

                        if (response.showRedirectConfirmation) {
                            urls = response.slugsData;

                            this.confirmModal = new ConfirmSlugChangeModal({
                                changedSlugs: this._getUrlsList(urls),
                                confirmState: this.createRedirectOption
                            })
                                .on('ok', this.modalApply.bind(this))
                                .on('confirm-option-changed', this.onConfirmModalOptionChange.bind(this))
                                .on('cancel', this.modalCancel.bind(this))
                                .open();
                        } else {
                            this.modalApply();
                        }
                    },
                    error: function() {
                        mediator.execute('hideLoading');
                        messenger.notificationFlashMessage('error', __('oro.ui.unexpected_error'));
                    }
                });

                return true;
            },

            /**
             * @return {boolean|Promise}
             */
            modalApply: function() {
                return ProductNamePopupEditorComponent.__super__.saveCurrentCell.call(this);
            },

            onConfirmModalOptionChange: function() {
                this.createRedirectOption = $(event.target).prop('checked');
            },

            modalCancel: function() {
                this.confirmModalOpened = false;
            },

            /**
             * @return {Object}
             */
            getServerUpdateData: function() {
                const data = this.view.getServerUpdateData();
                data.createRedirect = this.createRedirectOption;

                return data;
            },

            /**
                * @param {Object} urls
                * @returns {String}
                * @private
            */
            _getUrlsList: function(urls) {
                let list = '';
                for (const localization in urls) {
                    if (urls.hasOwnProperty(localization)) {
                        const oldSlug = _.macros('oroui::renderDirection')({
                            content: urls[localization].before
                        }).trim();
                        const newSlug = _.macros('oroui::renderDirection')({
                            content: urls[localization].after
                        }).trim();
                        list += '\n' + __(
                            'oro.redirect.confirm_slug_change.changed_localized_slug_item',
                            {
                                old_slug: oldSlug,
                                new_slug: newSlug,
                                purpose: localization
                            }
                        );
                    }
                }

                return list;
            }
        });

    return ProductNamePopupEditorComponent;
});
