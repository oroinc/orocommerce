define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const FrontendFiltersTogglePlugin = require('orofrontend/js/app/datafilter/plugins/frontend-filters-plugin');
    const CustomFiltersAction = require('orocustomtheme/FilterBundle/js/datagrid/actions/custom-filters-action').default;
    const CollapsesFilters = require('orocustomtheme/FilterBundle/js/datagrid/collapsed-filters').default;

    const FrontendCustomFiltersTogglePlugin = FrontendFiltersTogglePlugin.extend({
        /**
         * @inheritdoc
         */
        constructor: function FrontendCustomFiltersTogglePlugin(main, options) {
            FrontendCustomFiltersTogglePlugin.__super__.constructor.call(this, main, options);
        },

        initialize(options) {
            this.collapseFilters = new CollapsesFilters({datagrid: this.main});

            this.listenToOnce(this.main, 'filterManager:connected', () => {
                this.collapseFilters.onceFilterManagerConnected();
            });
            FrontendCustomFiltersTogglePlugin.__super__.initialize.call(this, options);
        },

        addAction(toolbarOptions) {
            if (this.changeBehavior()) {
                this.addCustomAction(toolbarOptions);
            } else {
                FrontendCustomFiltersTogglePlugin.__super__.addAction.call(this, toolbarOptions);
            }
        },

        addCustomAction(toolbarOptions) {
            const options = {
                datagrid: this.main,
                launcherOptions: {
                    className: 'toggle-filters-action btn btn--default btn--size-s',
                    launcherMode: 'icon-only',
                    icon: 'filter',
                    label: __('oro.filter.datagrid-toolbar.filters'),
                    ariaLabel: __('oro.filter.datagrid-toolbar.aria_label')
                },
                order: 650,
                fullscreenFilters: this.fullscreenFilters,
                collapseFilters: this.collapseFilters
            };

            toolbarOptions.addToolbarAction(new CustomFiltersAction(options));
        },

        changeBehavior: function() {
            return this.main.$el.parent().attr('data-server-render') !== void 0;
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            this.disable();

            if (this.collapseFilters && !this.collapseFilters.disposed) {
                this.collapseFilters.dispose();
                delete this.collapseFilters;
            }

            FrontendCustomFiltersTogglePlugin.__super__.dispose.call(this);
        }
    });
    return FrontendCustomFiltersTogglePlugin;
});
