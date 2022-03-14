define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');
    const DropdownSearch = require('orofrontend/blank/js/app/views/dropdown-search').default;

    const BUTTONS_ORDER = require('oroproduct/js/app/buttons-order').default;

    const BackendActionsPanel = ActionsPanel.extend({
        minimumResultsForSearch: 5,

        /**
         * @inheritdoc
         */
        constructor: function BackendActionsPanel(options) {
            BackendActionsPanel.__super__.constructor.call(this, options);
        },

        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function() {
            const currentLauncherIsPresent = !!_.filter(this.launchers, function(launcher) {
                return launcher.action.is_current === true;
            }).length;
            const isDropdown = this.$el.is('.dropdown-menu');

            this.launchers.forEach((launcher, index) => {
                let $el;
                const props = launcher.getTemplateData();
                const attributes = props.attributes || {};

                if (launcher.action.is_current) {
                    attributes['data-label'] = __('oro.product.frontend.actions_panel.action_postfix');
                }

                if (currentLauncherIsPresent) {
                    $el = this.findContainer(launcher, launcher.action.is_current);
                } else {
                    $el = this.findContainer(launcher, !index);
                }

                launcher.setOptions({withinDropdown: isDropdown, attributes: {...attributes}});
                $el.append(launcher.render().$el);
            });

            this.postRender();

            return this;
        },

        /**
         * @param {Object} launcher
         * @param {Boolean} pasteToExtraPanel
         */
        findContainer: function(launcher, pasteToExtraPanel) {
            let $el = this.$el;

            if (this.massActionsInSticky) {
                if (pasteToExtraPanel) {
                    $el = this.$el.find('[data-action-extra-panel]');
                    launcher.className = 'datagrid-massaction__action-trigger';
                } else {
                    $el = this.$el.find('[data-action-main-panel]');
                }
            }

            return $el;
        },

        postRender() {
            this.renderGroups();
            this.renderSearch();
            this.composeGroups();

            return this;
        },

        renderGroups() {
            if (this.massActionsInSticky) {
                return this;
            }

            const $actions = this.$el.children();
            let $groupsList = $();

            $actions.each(function() {
                const groupName = $(this).data('order') || 'add';
                const $group = $groupsList.filter(function() {
                    return $(this).is(`[data-group-order="${groupName}"]`);
                });

                $(this).find('.icon').addClass('fa fa--fw fa--аs-line');
                if ($group.length === 0) {
                    $groupsList = $groupsList.add(
                        $(`<ul class="items-group" data-group-order="${groupName}" role="menu""></ul>`)
                    );
                }
            });
            $groupsList.each(function() {
                const groupName = $(this).data('group-order');
                const $elementsInGroup = $actions.filter(function() {
                    return ($(this).data('order') || 'add') === groupName;
                });

                $elementsInGroup.wrapAll($(this)).wrap('<li role="menuitem"></li>');
            });

            return this;
        },

        renderSearch() {
            // 1. Verify that the number of elements has crossed the threshold
            // 2. Verify that search will not be rendered for buttons group
            if (this.$el.find('.items-group > li[role="menuitem"]').length <= this.minimumResultsForSearch) {
                return this;
            }

            // Dynamically add a container for a search
            if (this.$('[data-role="search"]').length === 0) {
                this.$el.children(':first').before(
                    $('<div data-role="search" data-group-order="search"></div>')
                );
            }

            const dropdownSearch = new DropdownSearch({
                minimumResultsForSearch: this.minimumResultsForSearch,
                searchClassName: 'dropdown-item dropdown-search',
                el: this.el
            });

            this.subview('dropdown-search', dropdownSearch);
            this.$el.prepend(dropdownSearch.render().el);

            return this;
        },

        composeGroups() {
            if (this.massActionsInSticky) {
                return this;
            }

            const $groups = this.$('[data-group-order]');

            $groups.detach().sort((a, b) => {
                const oderA = BUTTONS_ORDER[$(a).data('group-order')];
                const orderB = BUTTONS_ORDER[$(b).data('group-order')];

                if (oderA < orderB) {
                    return -1;
                } else if (oderA > orderB) {
                    return 1;
                }

                return 0;
            });

            this.$el.append($groups);
            $groups.filter(function() {
                return $(this).data('group-order') !== 'search';
            }).wrapAll('<div class="item-container"></div>');

            return this;
        }
    });

    return BackendActionsPanel;
});
