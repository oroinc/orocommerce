define(function(require) {
    'use strict';

    const ViewComponent = require('oroui/js/app/components/view-component');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const $ = require('jquery');
    const _ = require('underscore');

    const ShoppingListNotesEditableViewComponent = ViewComponent.extend({
        /**
         * @property {string}
         */
        className: null,

        /**
         * @property {integer}
         */
        shoppingListId: null,

        /**
         * @property {jQuery.Element}
         */
        $textarea: null,

        /**
         * @property {jQuery.Element}
         */
        $button: null,

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListNotesEditableViewComponent(options) {
            ShoppingListNotesEditableViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.className = options.className;
            this.shoppingListId = options.shoppingListId;

            this.$textarea = options._sourceElement.find('textarea');

            this.$button = options._sourceElement.find('[type="submit"]');
            this.$button.on('click', _.bind(this._onShoppingListNotesSubmit, this));
        },

        /**
         * Change shopping list notes event handler
         */
        _onShoppingListNotesSubmit: function() {
            $.ajax({
                method: 'PATCH',
                url: routing.generate(
                    'oro_api_frontend_patch_entity_data',
                    {
                        className: this.className,
                        id: this.shoppingListId
                    }
                ),
                data: JSON.stringify({
                    notes: this.$textarea.val()
                }),
                success: function(response) {
                    mediator.execute(
                        'showFlashMessage',
                        'success',
                        _.__('oro.frontend.shoppinglist.dialog.note.success')
                    );
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off('click', _.bind(this._onShoppingListNotesSubmit, this));

            ShoppingListNotesEditableViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListNotesEditableViewComponent;
});
