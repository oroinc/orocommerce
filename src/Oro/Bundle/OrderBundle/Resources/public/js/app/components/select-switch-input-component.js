define([
    'underscore',
    'jquery',
    'oroui/js/app/components/base/component'
], function(_, $, BaseComponent) {
    'use strict';

    return BaseComponent.extend({

        MODE_SELECT: 'select',
        MODE_INPUT: 'input',

        requiredOptions: [
            'mode',
            'choices',
            'value',
            '_sourceElement'
        ],

        $el: null,
        $mode: null,
        $choices: null,
        $select: null,
        $input: null,
        $select_to_input_btn: '.select-to-input-btn',
        $input_to_select_btn: '.input-to-select-btn',

        /**
         * @param options
         */
        initialize: function(options) {
            var missingProperties = _.filter(this.requiredOptions, _.negate(_.bind(options.hasOwnProperty, options)));
            if (missingProperties.length) {
                throw new Error(
                    'Following properties are required but weren\'t passed: ' +
                    missingProperties.join(', ') +
                    '.'
                );
            }

            this.$el = options._sourceElement;
            this.$mode = options.mode;
            this.$choices = options.choices;
            this.$select = this.$el.find('.selector');
            var name = this.$el.find('select').attr('name');
            var id = this.$el.find('select').attr('id');
            var validation = this.$el.find('select').attr('data-validation');
            this.$input = $('<input type="text" style="width: 100px; margin-left: 5px;">')
                .attr('id', id)
                .attr('name', name)
                .attr('data-validation', validation);
            this.$el.find('.input-group').prepend(this.$input);
            if (this.$mode == this.MODE_SELECT) {
                this._onInputToSelect();
            }else if (this.$mode == this.MODE_INPUT) {
                this._onSelectToInput();
                this.$el.find('input').val(options.value)
            }
            this.$el.find(this.$select_to_input_btn).on('click', _.bind(this._onSelectToInput, this));
            this.$el.find(this.$input_to_select_btn).on('click', _.bind(this._onInputToSelect, this));
        },

        /**
         * @param {jQuery.Event} e
         * @private
         */
        _onSelectToInput: function(e) {
            if (typeof e !== 'undefined') {
                e.preventDefault();
            }
            this.$mode = this.MODE_INPUT;
            this.$el.find('select').prop('disabled', 'disabled');
            this.$el.find('.selector').hide();
            this.$el.find(this.$select_to_input_btn).hide();
            this.$el.find('input').prop('disabled', false).val('').show();
            this.$el.find(this.$input_to_select_btn).show();
        },

        /**
         * @param {jQuery.Event} e
         * @private
         */
        _onInputToSelect: function(e) {
            if (typeof e !== 'undefined') {
                e.preventDefault();
            }
            this.$mode = this.MODE_SELECT;
            this.$el.find('input').prop('disabled', 'disabled').hide();
            this.$el.find(this.$input_to_select_btn).hide();
            this.$el.find('select').prop('disabled', false);
            this.$el.find('.selector').show();
            this.$el.find(this.$select_to_input_btn).show();
        }
    });
});
