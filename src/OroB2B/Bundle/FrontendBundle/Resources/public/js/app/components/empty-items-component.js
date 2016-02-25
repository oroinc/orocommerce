/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var EmptyItemsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    EmptyItemsComponent = BaseComponent.extend({
        initialize: function(options) {
            this.options = _.extend({
                eventName: 'item:delete',
                itemsSelector: '.itemsSelectorContainer',
                emptyBlockSelector: '.emptyBlockSelectorContainer',
                hiddenClass: 'hidden'
            }, options);
            this.$elem = options._sourceElement;

            mediator.on(this.options.eventName, _.bind(this.showEmptyMessage, this));
        },
        showEmptyMessage: function() {
            if (this.$elem.find(this.options.itemsSelector).length == 0) {
                this.$elem.remove();
                $(this.options.emptyBlockSelector).removeClass(this.options.hiddenClass);
            }
        }
    });

    return EmptyItemsComponent;
});
