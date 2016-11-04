// Toggle subblocks menu
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    require('jquery-ui');

    $.widget('oroui.toggleSublocks', {
        options: {
            itemSelector: '',
            siblingsSelector: '',
            subblock: '',
            parentItem: '',
            parentLink: '',
            activeLinkClass: ''
        },

        _create: function() {
            this.$el = this.element;

            this._super();
        },

        _init: function() {
            this.parentLinkEl = $(this.options.parentLink, this.$el);

            this._initEvents();
        },

        _initEvents: function() {
            var self = this;

            this.parentLinkEl.on('click', function (e) {
                e.preventDefault();
                self._toggleSubMenu($(this));
            });
        },

        _destroy: function() {
            $(this.options.itemSelector, this.$el).removeClass('hidden');
            $(this.options.subblock, this.$el).removeAttr('style');
        },

        _hideSiblings: function (element) {
            var siblingsElements = element.closest(this.options.siblingsSelector),
                parentItemSiblings;

            if (siblingsElements.length) {
                parentItemSiblings = siblingsElements.siblings();
            } else {
                parentItemSiblings = element.parent(this.options.parentItem).siblings(this.options.itemSelector);
            }

            parentItemSiblings.toggleClass('hidden');
        },

        _toggleSubMenu: function (element) {
            element.toggleClass(this.options.activeLinkClass)
                .parent(this.options.parentItem)
                .find('> '+this.options.subblock).toggle();

            this._hideSiblings(element);
        }
    });

    return 'toggleSublocks';
});
