define(function(require) {
    'use strict';

    var BackendPageSize;
    var PageSize = require('orodatagrid/js/datagrid/page-size');

    BackendPageSize = PageSize.extend({
        /** @property */
        themeOptions: {
            optionPrefix: 'backendpagesize',
            el: '[data-grid-pagesize]'
        },

        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            if (options.items) {
                this.items = options.items;
            }

            this.collection = options.collection;
            this.listenTo(this.collection, 'add', this.render);
            this.listenTo(this.collection, 'remove', this.render);
            this.listenTo(this.collection, 'reset', this.render);

            this.enabled = options.enable !== false;
            this.hidden = options.hide === true;

            BackendPageSize.__super__.initialize.call(this, options);
        },

        render: function() {


            var currentSizeLabel = _.filter(
                this.items,
                _.bind(
                    function(item) {
                        return item.size === undefined ?
                        this.collection.state.pageSize === item : this.collection.state.pageSize === item.size;
                    },
                    this
                )
            );

            if (currentSizeLabel.length > 0) {
                currentSizeLabel = _.isUndefined(currentSizeLabel[0].label) ?
                    currentSizeLabel[0] : currentSizeLabel[0].label;
            } else {
                currentSizeLabel = this.items[0];
            }

            //this.$el.append($(this.template({
            //    disabled: !this.enabled || !this.collection.state.totalRecords,
            //    collectionState: this.collection.state,
            //    items: this.items,
            //    currentSizeLabel: currentSizeLabel
            //})));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }

    });
    return BackendPageSize;
});
