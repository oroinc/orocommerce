define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const template = require('tpl-loader!oroproduct/templates/datagrid/fullscreen-sorting.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const FullscreenPopupView = require('orofrontend/default/js/app/views/fullscreen-popup-view');

    const FullscreenSorting = BaseView.extend({
        keepElement: true,

        autoRender: true,

        template: template,

        popupHandlerSelector: '[data-role="fullscreen-sorting"]',

        popupContentSelector: '[data-role="fullscreen-sorting-content"]',

        sortingSwitcherSelector: '[data-role="fullscreen-sorting-switcher"]',

        /**
         * @inheritdoc
         */
        constructor: function FullscreenSorting(options) {
            FullscreenSorting.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el.addClass('hidden');

            this.$view = $(this.template(this.getTemplateData()));
            this.$popupHandler = this.$view.filter(this.popupHandlerSelector);
            this.$popupContent = this.$view.filter(this.popupContentSelector);
            this.$sortingSwitcher = this.$popupContent.find(this.sortingSwitcherSelector);
            this.initEvents();
        },

        initEvents: function() {
            this.$popupHandler.on({
                click: this.onFullscreenPopup.bind(this)
            });
        },

        onFullscreenPopup: function(e) {
            e.preventDefault();

            this.$sortingSwitcher.on({
                change: this.onChange.bind(this)
            });

            this.fullscreenView = new FullscreenPopupView({
                contentElement: this.$popupContent,
                popupIcon: 'fa-chevron-left'
            });
            this.fullscreenView.on('close', this.onClosePopup.bind(this));
            this.fullscreenView.show();
        },

        onClosePopup: function() {
            if (this.fullscreenView) {
                this.fullscreenView.dispose();
                delete this.fullscreenView;

                this.$sortingSwitcher.off();
            }
        },

        onChange: function(e) {
            this.$el.val($(e.target).val()).trigger('change');
            this.fullscreenView.trigger('close');
        },

        getTemplateData: function() {
            const itemsList = [];

            const currentVal = this.$el.val();
            const groupName = 'sorting-' + _.random(1000, 10000);

            this.$el.find('option').each(function() {
                const val = $(this).prop('value');

                itemsList.push({
                    name: groupName,
                    value: val,
                    title: $(this).text(),
                    checked: (currentVal === val)
                });
            });

            return {
                itemsList: itemsList
            };
        },

        render: function() {
            this.$el.before(this.$popupHandler);
        },

        dispose: function() {
            this.onClosePopup();
            this.$popupHandler.off();
            this.$view.remove();
            this.$el.removeClass('hidden');

            delete this.$view;
            delete this.$popupHandler;
            delete this.$popupContent;
            delete this.$sortingSwitcher;

            FullscreenSorting.__super__.dispose.call(this);
        }
    });

    return FullscreenSorting;
});
