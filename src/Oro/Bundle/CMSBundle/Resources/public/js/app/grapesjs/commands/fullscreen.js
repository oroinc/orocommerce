import $ from 'jquery';

export default {
    isEnabled() {
        return this.$container.hasClass('fullscreen-mode');
    },

    onKeyUp(event) {
        if (event.keyCode === 27 && this.isEnabled()) {
            this.stop(this.editor, this.sender);
        }
    },

    enable() {
        this.$container.addClass('fullscreen-mode');
        this.$container.on('inserted.bs.tooltip.fullscreenMode', ({target}) => {
            if (!$(target).data('storeBoundary')) {
                $(target).data('storeBoundary', $(target).data('bs.tooltip').config.boundary);
                $(target).data('bs.tooltip').config.boundary = 'viewport';
            }
        });
        this.$container.on('shown.bs.tooltip.fullscreenMode', ({target}) => {
            if ($(target).data('storeBoundary')) {
                $(target).data('bs.tooltip').config.boundary = $(target).data('storeBoundary');
                $(target).removeData('storeBoundary');
            }
        });
        $(window).on('keyup.fullscreenMode', this.onKeyUp.bind(this));
    },

    disable() {
        if (this.isEnabled()) {
            this.$container.removeClass('fullscreen-mode');
            this.$container.tooltip('hide');
            this.$container.off('.fullscreenMode');
            $(window).off('keyup.fullscreenMode');
        }
    },

    run(editor, sender) {
        this.editor = editor;
        this.sender = sender;
        this.$container = $(editor.getContainer());
        this.enable();
        editor.trigger('change:canvasOffset');
    },

    stop(editor, sender) {
        if (sender && sender.set) {
            sender.set('active', false);
        }

        this.disable();

        if (editor) {
            editor.trigger('change:canvasOffset');
        }
    }
};
