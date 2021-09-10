define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const PageableCollection = require('orodatagrid/js/pageable-collection');
    const LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    const tools = require('oroui/js/tools');
    const error = require('oroui/js/error');

    const BackendPageableCollection = PageableCollection.extend({
        /**
         * @inheritdoc
         */
        constructor: function BackendPageableCollection(...args) {
            BackendPageableCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @param {object} options
         */
        fetch: function(options) {
            this.trigger('beforeFetch', this, options);

            this._fetch(options);
        },

        /**
         * @param {object} options
         * @private
         */
        _fetch: function(options) {
            this.trigger('gridContentUpdate');
            options = _.defaults(options || {}, {reset: true});

            const state = this._checkState(this.state);

            let data = options.data || {};

            // set up query params
            const url = options.url || _.result(this, 'url') || '';
            const qsi = url.indexOf('?');
            if (qsi !== -1) {
                _.extend(data, tools.unpackFromQueryString(url.slice(qsi + 1)));
            }

            options.data = data;

            data.appearanceType = state.appearanceType;
            data = this.processQueryParams(data, state);
            this.processFiltersParams(data, state);

            LayoutSubtreeManager.get('product_datagrid', options.data, function(content) {
                const $data = $('<div/>').append(content);

                if ($data.find('[data-server-render]').length) {
                    const options = $data.find('[data-server-render]').data('page-component-options');

                    if (options) {
                        const params = {
                            responseJSON: options,
                            gridContent: $data.find('.grid-body')
                        };

                        mediator.trigger('grid-content-loaded', params);
                    } else {
                        error.showError(_.__('oro_frontend.datagrid.requires.options'));
                    }
                } else {
                    error.showError(_.__('oro_frontend.datagrid.requires.data'));
                }
            });
        }
    });

    return BackendPageableCollection;
});
