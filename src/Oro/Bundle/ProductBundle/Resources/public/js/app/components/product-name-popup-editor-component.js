define(function(require) {
    'use strict';

    var ProductNamePopupEditorComponent;
    var CellPopupEditorComponent = require('orodatagrid/js/app/components/cell-popup-editor-component');
    var ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ProductNamePopupEditorComponent =
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
             * @inheritDoc
             */
            constructor: function ProductNamePopupEditorComponent() {
                ProductNamePopupEditorComponent.__super__.constructor.apply(this, arguments);
            },

            /**
             * @inheritDoc
             */
            initialize: function(options) {
                this.options = _.defaults(options || {}, this.options);

                return ProductNamePopupEditorComponent.__super__.initialize.apply(this, arguments);
            },

            /**
             * @inheritDoc
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

                var urls = {};
                var that = this;

                mediator.execute('showLoading');
                $.ajax({
                    url: routing.generate(this.options.changedSlugsUrl, {id: this.view.model.id}),
                    type: 'POST',
                    data: {productName: this.view.getValue()},
                    dataType: 'json',
                    success: function(response) {
                        mediator.execute('hideLoading');

                        if (response.showRedirectConfirmation) {
                            urls = response.slugsData;

                            this.confirmModal = new ConfirmSlugChangeModal({
                                changedSlugs: that._getUrlsList(urls),
                                confirmState: that.createRedirectOption
                            })
                                .on('ok', _.bind(that.modalApply, that))
                                .on('confirm-option-changed', _.bind(that.onConfirmModalOptionChange, that))
                                .on('cancel', _.bind(that.modalCancel, that))
                                .open();
                        } else {
                            that.modalApply();
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
                var data = this.view.getServerUpdateData();
                data.createRedirect = this.createRedirectOption;

                return data;
            },

            /**
                * @param {Object} urls
                * @returns {String}
                * @private
            */
            _getUrlsList: function(urls) {
                var list = '';
                for (var localization in urls) {
                    if (urls.hasOwnProperty(localization)) {
                        list += '\n' + __(
                            'oro.redirect.confirm_slug_change.changed_localized_slug_item',
                            {
                                old_slug: urls[localization].before,
                                new_slug: urls[localization].after,
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
