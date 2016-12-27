define(function(require) {
    'use strict';

    var BackendPageableCollection;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var PageableCollection = require('orodatagrid/js/pageable-collection');
    var LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    var tools = require('oroui/js/tools');

    BackendPageableCollection = PageableCollection.extend({
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

            var state = this._checkState(this.state);

            var data = options.data || {};

            // set up query params
            var url = options.url || _.result(this, 'url') || '';
            var qsi = url.indexOf('?');
            if (qsi !== -1) {
                _.extend(data, tools.unpackFromQueryString(url.slice(qsi + 1)));
            }

            options.data = data;

            data.appearanceType = state.appearanceType;
            data = this.processQueryParams(data, state);
            this.processFiltersParams(data, state);

            LayoutSubtreeManager.get('product_datagrid', options.data, function(content) {
                var $data = $('<div/>').append(content);
                var options = $data.find('[data-page-component-name=frontend-product-search-grid]')
                                   .data('page-component-options');
                var params = {
                    responseJSON: options,
                    gridContent: $data.find('.grid-body')
                };

                mediator.trigger('grid-content-loaded', params);
            });
        }
    });

    return BackendPageableCollection;
});
