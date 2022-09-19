define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');
    const DropdownSearch = require('orofrontend/default/js/app/views/dropdown-search').default;

    const BUTTONS_ORDER = require('oroproduct/js/app/buttons-order').default;

    const BackendActionsPanel = ActionsPanel.extend({
        /**
         * @inheritdoc
         */
        constructor: function BackendActionsPanel(options) {
            BackendActionsPanel.__super__.constructor.call(this, options);
        },

        minimumResultsForSearch: 5,

        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function() {
            const addActionsCount = this.launchers.filter(launcher => {
                return launcher.action.type === 'addproducts';
            }).length;

            this.launchers.forEach(launcher => {
                const props = launcher.getTemplateData();
                const attributes = props.attributes || {};

                if (addActionsCount > 1 && launcher.action.is_current) {
                    attributes['data-label'] = __('oro.product.frontend.actions_panel.action_postfix');
                }

                launcher.setOptions({attributes});
            });
            BackendActionsPanel.__super__.render.call(this);

            this.postRender();

            return this;
        },

        renderMainLauncher() {
            const launcher = this.launchers.filter(launcher => launcher.action.is_current)[0] || this.launchers[0];

            this.$el.empty();
            this.$el.append(launcher.render().$el);
            launcher.trigger('appended');

            return this;
        },

        postRender() {
            this.renderGroups();
            this.renderSearch();
            this.composeGroups();
        },

        renderGroups() {
            const $actions = this.$el.children();
            let $groupsList = $();

            $actions.each(function() {
                const groupName = $(this).data('order') || 'add';
                const $group = $groupsList.filter(function() {
                    return $(this).is(`[data-group-order="${groupName}"]`);
                });

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
            if (this.$el.find('.items-group > li[role="menuitem"]').length <= this.minimumResultsForSearch) {
                return;
            }

            // Dynamically add a container for a search
            if (this.$('[data-role="search"]').length === 0) {
                this.$el.children(':first').before(
                    $('<div class="dropdown-item" data-role="search" data-group-order="search"></div>')
                );
            }

            const dropdownSearch = new DropdownSearch({
                minimumResultsForSearch: this.minimumResultsForSearch,
                el: this.el
            });

            this.subview('dropdown-search', dropdownSearch);

            return this;
        },

        composeGroups() {
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
        },

        getMainLauncher() {
            return this.launchers.filter(launcher => launcher.action.is_current)[0] || this.launchers[0];
        }
    });

    return BackendActionsPanel;
});
