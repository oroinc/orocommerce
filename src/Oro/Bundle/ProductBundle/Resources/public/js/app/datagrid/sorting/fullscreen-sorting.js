define(function(require) {
    'use strict';

    var FullscreenSorting;
    var $ = require('jquery');
    var _ = require('underscore');
    var template = require('tpl!oroproduct/templates/datagrid/fullscreen-sorting.html');
    var BaseView = require('oroui/js/app/views/base/view');
    var FullscreenPopupView = require('orofrontend/blank/js/app/views/fullscreen-popup-view');

    FullscreenSorting = BaseView.extend({
        keepElement: true,

        autoRender: true,

        template: template,

        popupHandlerSelector: '[data-role="fullscreen-sorting"]',

        popupContentSelector: '[data-role="fullscreen-sorting-content"]',

        sortingSwitcherSelector: '[data-role="fullscreen-sorting-switcher"]',

        /**
         * @constructor
         *
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
                click: _.bind(this.onFullscreenPopup, this)
            });
        },

        onFullscreenPopup: function(e) {
            e.preventDefault();

            this.$sortingSwitcher.on({
                change: _.bind(this.onChange, this)
            });

            this.fullscreenView = new FullscreenPopupView({
                contentElement: this.$popupContent,
                popupIcon: 'fa-chevron-left'
            });
            this.fullscreenView.on('close', _.bind(this.onClosePopup, this));
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
            var itemsList = [];

            var currentVal = this.$el.val();
            var groupName = 'sorting-' + _.random(1000, 10000);

            this.$el.find('option').each(function() {
                var val = $(this).prop('value');

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
