/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductUnitSelectionLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    ProductUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $container : null,

        /**
         * @property {Object}
         */
        $addButton : null,

        /**
         * @property {array}
         */
        precisions : {},

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }

            var precisions = options['precisions'];
            if (precisions) {
                this.precisions = precisions;
            }

            this.$container = $(containerId);
            this.$container
                .on('content:changed', _.bind(this.onChange, this))
                .on('content:remove', _.bind(this.onRemoveItem, this));

            this.$addButton = this.$container.next('a.add-list-item');
            this.$container.trigger('content:changed');
        },

        /**
         * Handle change select
         */
        onChange: function () {
            var selects = this.$container.find('select'),
                self = this;

            selects.each( function(index) {
                var select = $(this);

                selects.each(function(_index){
                    if (index == _index) {
                        return;
                    }

                    var option = $(this).find("option[value='" + select.val() + "']");

                    if (option) {
                        option.remove();
                    }
                });

                if (select.find('option').length <= 1) {
                    self.$addButton.hide();
                }

                var option = select.find('option:selected');

                if (option.val() != select.data('prevValue') && !select.attr('disabled')) {
                    var value = self.precisions[option.val()];

                    if (value != undefined) {
                        select.parents('div.oro-multiselect-holder').find('input').val(value);
                    }
                }

                select
                    .data('prevValue', option.val())
                    .data('prevText', option.text())
                    .on('change', _.bind(self.onSelectChange, self));
            });
        },

        /**
         * Handle remove item
         *
         * @param {jQuery.Event} e
         */
        onRemoveItem: function (e) {
            var option = $(e.target).find('select option:selected');

            if (option) {
                this.addOptionToAllSelects(option.val(), option.text());
                this.$addButton.show();
            }
        },

        /**
         * Handle select change
         *
         * @param {jQuery.Event}  e
         */
        onSelectChange: function (e) {
            var select = $(e.target);

            this.addOptionToAllSelects(select.data('prevValue'), select.data('prevText'));
            this.onChange();
        },

        /**
         * Add available options to selects
         *
         * @param {String} value
         * @param {String} text
         */
        addOptionToAllSelects: function (value, text) {
            this.$container.find('select').each(function () {
                var select = $(this);

                if (select.data('prevValue') != value) {
                    select.append('<option value="' + value + '">' + text + '</option>');
                }
            });
        }
    });

    return ProductUnitSelectionLimitationsComponent;
});
