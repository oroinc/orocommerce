define(function(require) {
    'use strict';

    var SinglePageCheckoutComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var SinglePageCheckoutFormView = require('orocheckout/js/app/views/single-page-checkout-form-view');

    SinglePageCheckoutComponent = BaseComponent.extend({
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
                    'checkout_button_continue_right_wrapper'
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
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutComponent(options) {
            SinglePageCheckoutComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});
            this.queue = $({});

            this.formView = new SinglePageCheckoutFormView({
                el: this.options._sourceElement.closest('form'),
                entityId: this.options.entityId
            });

            this.formView.on('after-check-form', _.bind(this.onAfterCheckForm, this));
            this.formView.on('submit-form', _.bind(this.onSubmit, this));

            SinglePageCheckoutComponent.__super__.initialize.call(this, this.options);
        },

        /**
         * @param {string} serializedData
         * @param {jQuery} $field
         */
        onAfterCheckForm: function(serializedData, $field) {
            var parameters = this._getRequestParameters($field);

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

            var url = this.options.transitionUrl +
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
                var eventData = {stopped: false, responseData: response.responseData};
                mediator.trigger('checkout:place-order:response', eventData);
                if (eventData.stopped) {
                    return;
                }
            }

            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                var $response = $('<div/>').html(response);

                mediator.trigger('checkout-content:before-update');

                var $content = $(this.options.checkoutContentSelector);
                $content.html($response.find(this.options.checkoutContentSelector).html());

                var $flashNotifications = $response.find(this.options.checkoutFlashNotifications);
                _.each($flashNotifications, function(element) {
                    var $element = $(element);
                    var type = $element.data('type');
                    var message = $element.data('message');
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
            var targetLayoutBlocks = this.options.targetLayoutBlocks || {};
            var parameters = $.extend(true, {}, this.options.defaultParameters);
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
            var dfd = $.Deferred();
            var promise = dfd.promise();
            var self = this;

            self.queue.queue(function(next) {
                self._doRequest(next, dfd, ajaxOpts, parameters);
            });

            promise.abort = function(statusText) {
                if (self.jqXHR) {
                    return self.jqXHR.abort(statusText);
                }

                var queue = self.queue.queue();
                var index = $.inArray(function(next) {
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

            var layoutBlockIds = parameters.layout_block_ids || [];

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
            _.each(layoutBlockIds, (function(blockId) {
                if (blockId in LayoutSubtreeManager.viewsCollection) {
                    this.layoutSubTreeViews[blockId] = LayoutSubtreeManager.viewsCollection[blockId];
                }
            }).bind(this));
        },

        _beforeLayoutSubTreeViewContentLoading: function() {
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
            _.each(this.layoutSubTreeViews, (function(view) {
                view.afterContentLoading();
            }));
        },

        _contentLayoutSubTreeViewContentLoadingFail: function() {
            _.each(this.layoutSubTreeViews, (function(view) {
                view.contentLoadingFail();
            }));
        },

        /**
         * @inheritDoc
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
