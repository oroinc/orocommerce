define(function(require) {
    'use strict';

    const LazyInitView = require('orofrontend/js/app/views/lazy-init-view');

    /**
     * @class DataGridProductLazyInitView
     * @extends LazyInitView
     */
    const DataGridProductLazyInitView = LazyInitView.extend({
        constructor: function DataGridProductLazyInitView(options) {
            DataGridProductLazyInitView.__super__.constructor.call(this, options);
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            DataGridProductLazyInitView.__super__.initialize.call(this, options);

            if (this.lazy === 'scroll') {
                this.listenToOnce(this.collection, {
                    'reset': this.dispose,
                    'backgrid:selectAllVisible': this._onSelectAllVisible
                });
            }
        },

        /**
         * Bind MassAction selectAllVisible trigger with lazy-view
         */
        _onSelectAllVisible: function() {
            this.initLazyView().then(() => {
                this.collection.trigger('backgrid:selectAllVisible');
            });
        }
    });

    return DataGridProductLazyInitView;
});
