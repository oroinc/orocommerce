/** @lends SwitchableEditableViewComponent */
define(function(require) {
    'use strict';

    var SwitchableEditableViewComponent;
    var AttachedEditableViewComponent = require('orofrontend/js/app/components/attached-editable-view-component');
    var _ = require('underscore');

    SwitchableEditableViewComponent = AttachedEditableViewComponent.extend(/** @exports SwitchableEditableViewComponent.prototype */{
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.switcher = options._sourceElement.find('[data-role="start-editing"]');
            this.switcher.on('click', _.bind(this.onSwitcherChange, this));

            SwitchableEditableViewComponent.__super__.initialize.call(this, options);
        },

        startEditing: function() {
            this.onSwitcherChange();
        },

        onSwitcherChange: function() {
            if (this.switcher.is(':checked')) {
                this.enterEditMode();
            } else {
                this.hideEditor();
            }
        },

        enterEditMode: function() {
            SwitchableEditableViewComponent.__super__.enterEditMode.call(this);

            this.showEditor();

            return this.editorView;
        },

        hideEditor: function() {
            if (this.editorView) {
                this.editorView.$el.val('');
                this.saveCurrentCell();
            }

            this.wrapper.$el.hide();
        },

        showEditor: function() {
            this.wrapper.$el.show();
        }
    });

    return SwitchableEditableViewComponent;
});
