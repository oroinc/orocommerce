define(function(require) {
    'use strict';

    const _ = require('underscore');
    const PageSize = require('orodatagrid/js/datagrid/page-size');

    const BackendPageSize = PageSize.extend({
        /** @property */
        themeOptions: {
            optionPrefix: 'backendpagesize',
            el: '[data-grid-pagesize]'
        },

        /**
         * @inheritdoc
         */
        constructor: function BackendPageSize(options) {
            BackendPageSize.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BackendPageSize.__super__.initialize.call(this, options);
        },

        onChangePageSize: function(e) {
            const obj = {};
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendPageSize.__super__.onChangePageSize.call(this, e);
            } else {
                this.render();
            }
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const $select = this.$el.find('[data-grid-pagesize-selector]');
            const currentSizeLabel = _.filter(this.items, item => {
                return item.size === undefined
                    ? this.collection.state.pageSize === item : this.collection.state.pageSize === item.size;
            });

            $select
                .find('option')
                .removeAttr('selected', false)
                .filter('[value=' + currentSizeLabel[0] + ']')
                .attr('selected', true);

            $select.inputWidget('val', currentSizeLabel[0]);

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }

    });
    return BackendPageSize;
});
