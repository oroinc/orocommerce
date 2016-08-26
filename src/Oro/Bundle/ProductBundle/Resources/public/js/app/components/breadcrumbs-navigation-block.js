define(function(require) {
    'use strict';

    var BreadcrumbsNavigationBlock;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');

    BreadcrumbsNavigationBlock = BaseComponent.extend({
        /**
         * @property
         */
        $element: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.$element = options['_sourceElement'];

            mediator.on('datagrid_filters:update', $.proxy(this, 'updateFiltersInfo'));

            BreadcrumbsNavigationBlock.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            BreadcrumbsNavigationBlock.__super__.dispose.call(this);
        },

        updateFiltersInfo: function(datagrid) {
            console.log(datagrid);
            
            var currentFilters = [];
            var filterState;

            for (var filterName in datagrid['collection']['state']['filters']) {
                if (!datagrid['collection']['state']['filters'].hasOwnProperty(filterName)) {
                    continue;
                }

                filterState = datagrid['collection']['state']['filters'][filterName];

                datagrid['metadata']['filters'].forEach(function (filterDefinition) {
                    if (filterDefinition['name'] == filterName) {
                        var choiceTypeName;

                        filterDefinition['choices'].forEach(function (choiceDefinition) {
                            if (choiceDefinition['value'] == filterState['type']) {
                                choiceTypeName = choiceDefinition['label'];
                            }
                        });

                        currentFilters.push({
                            name: filterDefinition['name'],
                            label: filterDefinition['label'],
                            value: filterState['value'],
                            type: choiceTypeName
                        });
                    }
                });
            }

            if (currentFilters.length === 0) {
                $('.filters-info', this.$element).html('');

                return;
            }

            var buildFilterString = function(filter) {
                return filter['label'] + ' ' + filter['type'] + ' ' + filter['value'];
            };

            var filtersStrings = [];

            currentFilters.forEach(function(filter) {
                filtersStrings.push(buildFilterString(filter));
            });

            var filtersString = '[' + filtersStrings.join(', ') + ']';

            console.log(filtersString);

            $('.filters-info', this.$element).html(filtersString);
        }
    });

    return BreadcrumbsNavigationBlock;
});
