import {hasSimpleTextContent} from './variants/variant-utils';

const EMPTY_CONTAINER_CLASS = 'link--empty-container';

export default (BaseTypeView, {editor}) => {
    return BaseTypeView.extend({
        editor,

        events: {
            dblclick: 'onDoubleClick'
        },

        constructor: function LinkTypeView(...args) {
            return LinkTypeView.__super__.constructor.apply(this, args);
        },

        onRender() {
            this.listenTo(this.model, 'change:containerMode', this.updateEmptyContainerClass);
            this.listenTo(this.model.get('components'), 'add remove reset', this.updateEmptyContainerClass);
            this.updateEmptyContainerClass();

            const styleId = this.model.get('linkStyle');
            const style = editor.LinkStyleRegistry.get(styleId);

            if (!style || !style.traits.some(t => (t.name || t) === 'text')) {
                return;
            }

            const traitText = this.model.getTrait('text');

            if (traitText && hasSimpleTextContent(this.model)) {
                traitText.setValue(this.el.innerText);
            }
        },

        updateEmptyContainerClass() {
            const isContainer = this.model.get('containerMode');
            const hasChildren = this.model.get('components').length > 0;

            if (isContainer && !hasChildren) {
                this.el.classList.add(EMPTY_CONTAINER_CLASS);
            } else {
                this.el.classList.remove(EMPTY_CONTAINER_CLASS);
            }
        },

        onDoubleClick(e) {
            e.stopPropagation();

            const actionId = this.model.get('mainToolbarAction');
            const toolbar = this.model.get('toolbar') || [];
            const item = toolbar.find(ti => ti.id === actionId);

            if (item && item.command) {
                if (typeof item.command === 'function') {
                    item.command(this.editor);
                } else {
                    this.editor.runCommand(item.command);
                }
            }
        }
    });
};
