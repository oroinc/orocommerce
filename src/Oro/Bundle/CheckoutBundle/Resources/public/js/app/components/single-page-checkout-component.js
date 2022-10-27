define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const SinglePageCheckoutFormView = require('orocheckout/js/app/views/single-page-checkout-form-view');

    const SinglePageCheckoutComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            entityId: null,
            saveStateUrl: null,
            transitionUrl: null,
            targetLayoutBlocks: null,
            checkoutContentSelector: '[data-role="checkout-content"]',
            checkoutFlashNotifications: '[data-role="checkout-flash-notifications"]',
            defaultParameters: {
                layout_block_ids: [
                    'checkout_button_continue_right_wrapper',
                    'checkout_consent_subtree'
                ],
                _widgetContainer: 'ajax',
                _wid: 'ajax_checkout'
            }
        },

        /**
         * @property
         */
        queue: null,

        /**
         * @property
         */
        jqXHR: null,

        /**
         * @inheritdoc
         */
        constructor: function SinglePageCheckoutComponent(options) {
            SinglePageCheckoutComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});
            this.queue = $({});

            this.formView = new SinglePageCheckoutFormView({
                el: this.options._sourceElement.closest('form'),
                entityId: this.options.entityId
            });

            this.formView.on('after-check-form', this.onAfterCheckForm.bind(this));
            this.formView.on('submit-form', this.onSubmit.bind(this));

            SinglePageCheckoutComponent.__super__.initialize.call(this, this.options);
        },

        /**
         * @param {string} serializedData
         * @param {jQuery} $field
         */
        onAfterCheckForm: function(serializedData, $field) {
            const parameters = this._getRequestParameters($field);

            this._saveStateDeferred({
                dataType: 'json',
                url: this.options.saveStateUrl,
                method: 'POST',
                data: serializedData + '&' + $.param(parameters)
            }, parameters);
        },

        /**
         * @param {string} data
         */
        onSubmit: function(data) {
            mediator.execute('showLoading');

            const url = this.options.transitionUrl +
                (-1 !== _.indexOf(this.options.transitionUrl, '?') ? '&' : '?') +
                $.param({_widgetContainer: 'ajax', _wid: 'ajax_checkout'});

            $.ajax({
                url: url,
                method: 'POST',
                data: data
            })
                .done(this.onSuccess.bind(this))
                .fail(function() {
                    mediator.execute('hideLoading');
                    mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
                });
        },

        onSuccess: function(response) {
            if (response.hasOwnProperty('responseData')) {
                const eventData = {stopped: false, responseData: response.responseData};
                mediator.trigger('checkout:place-order:response', eventData);
                if (eventData.stopped) {
                    return;
                }
            }

            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                const $response = $('<div/>').html(response);

                mediator.trigger('checkout-content:before-update');

                const $content = $(this.options.checkoutContentSelector);
                $content.html($response.find(this.options.checkoutContentSelector).html());

                const $flashNotifications = $response.find(this.options.checkoutFlashNotifications);
                _.each($flashNotifications, function(element) {
                    const $element = $(element);
                    const type = $element.data('type');
                    let message = $element.data('message');
                    message = message.replace(/\n/g, '<br>');
                    _.delay(function() {
                        mediator.execute('showFlashMessage', type, message);
                    }, 100);
                });

                mediator.trigger('checkout-content:updated');
                mediator.trigger('layout:reposition');
            }

            mediator.execute('hideLoading');
        },

        /**
         * @param {jQuery} $field
         */
        _getRequestParameters: function($field) {
            const targetLayoutBlocks = this.options.targetLayoutBlocks || {};
            const parameters = $.extend(true, {}, this.options.defaultParameters);
            $.each(targetLayoutBlocks, function(selector, layoutBlocks) {
                if ($field.is(selector)) {
                    parameters.layout_block_ids = $.merge(parameters.layout_block_ids, layoutBlocks);
                }
            });

            return parameters;
        },

        /**
         * @param {Object} ajaxOpts
         * @param {Object} parameters
         */
        _saveStateDeferred: function(ajaxOpts, parameters) {
            const dfd = $.Deferred();
            const promise = dfd.promise();
            const self = this;

            self.queue.queue(function(next) {
                self._doRequest(next, dfd, ajaxOpts, parameters);
            });

            promise.abort = function(statusText) {
                if (self.jqXHR) {
                    return self.jqXHR.abort(statusText);
                }

                const queue = self.queue.queue();
                const index = $.inArray(function(next) {
                    self._doRequest(next, dfd, ajaxOpts, parameters);
                }, queue);

                if (index > -1) {
                    queue.splice(index, 1);
                }

                dfd.rejectWith(ajaxOpts.context || ajaxOpts, [promise, statusText, '']);

                return promise;
            };

            return promise;
        },

        /**
         * @param {Function} next
         * @param {Object} dfd
         * @param {Object} ajaxOpts
         * @param {Object} parameters
         */
        _doRequest: function(next, dfd, ajaxOpts, parameters) {
            this.formView.trigger('before-save-state');

            const layoutBlockIds = parameters.layout_block_ids || [];

            this._prepareLayoutSubTreeViews(layoutBlockIds);
            this._beforeLayoutSubTreeViewContentLoading();
            this.jqXHR = $.ajax(ajaxOpts)
                .done(function(response) {
                    this._setLayoutSubTreeViewContent(response);
                    this._afterLayoutSubTreeViewContentLoading();
                    this.formView.trigger('after-save-state');
                    dfd.resolve(response);
                }.bind(this))
                .fail(function(reject) {
                    this._contentLayoutSubTreeViewContentLoadingFail();
                    dfd.reject(reject);
                }.bind(this))
                .then(next);
        },

        /**
         * @param {Array} layoutBlockIds
         */
        _prepareLayoutSubTreeViews: function(layoutBlockIds) {
            this.layoutSubTreeViews = {};

            _.each(layoutBlockIds, blockId => {
                if (blockId in LayoutSubtreeManager.viewsCollection) {
                    const view = LayoutSubtreeManager.viewsCollection[blockId];
                    this.listenTo(view, 'dispose', () => (delete this.layoutSubTreeViews[blockId]));
                    this.layoutSubTreeViews[blockId] = view;
                }
            });
        },

        _beforeLayoutSubTreeViewContentLoading: function() {
            mediator.trigger('single-page-checkout:before-layout-subtree-content-loading');
            _.each(this.layoutSubTreeViews, (function(view) {
                view.beforeContentLoading();
            }));
        },

        /**
         * @param {Array} response
         */
        _setLayoutSubTreeViewContent: function(response) {
            _.each(this.layoutSubTreeViews, (function(view, blockId) {
                if (blockId in response) {
                    if (blockId === 'checkout_order_summary_totals_wrapper') {
                        view.useHiddenElement = true;
                    }
                    view.setContent(response[blockId]);
                }
            }));
        },

        _afterLayoutSubTreeViewContentLoading: function() {
            mediator.trigger('single-page-checkout:after-layout-subtree-content-loading');
            _.each(this.layoutSubTreeViews, view => {
                view.afterContentLoading();
                this.stopListening(view);
            });
        },

        _contentLayoutSubTreeViewContentLoadingFail: function() {
            mediator.trigger('single-page-checkout:layout-subtree-content-loading-fail');
            _.each(this.layoutSubTreeViews, view => {
                view.contentLoadingFail();
                this.stopListening(view);
            });
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (!this.formView.disposed) {
                this.formView.dispose();
                delete this.formView;
            }

            SinglePageCheckoutComponent.__super__.dispose.call(this);
        }
    });

    return SinglePageCheckoutComponent;
});
