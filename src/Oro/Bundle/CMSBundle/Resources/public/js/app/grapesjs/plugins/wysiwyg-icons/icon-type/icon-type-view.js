const noIconPlaceholder = `<rect width="100%" height="100%" style="fill:#f3f3f3;stroke-width:3;stroke:rgb(0,0,0)" />
<line x1="0" y1="0" x2="100%" y2="100%" stroke-width="2" stroke="black"/>
<line x1="0" y1="100%" x2="100%" y2="0" stroke-width="2" stroke="black"/>`;

export default (BaseTypeView, {editor} = {}) => {
    const IconTypeView = BaseTypeView.extend({
        editor,

        noIconPlaceholder,

        events: {
            dblclick: 'onActive',
            mousedown: 'onMouseDown',
            mousemove: 'onMouseMove',
            mouseup: 'onMouseUp'
        },

        constructor: function IconTypeView(...args) {
            return IconTypeView.__super__.constructor.apply(this, args);
        },

        init() {
            this.listenTo(this.model, 'change:iconId', this.onIconUpdate);
            this.listenTo(this.em, 'changeTheme', this.onRender.bind(this));
        },

        _createElement(tagName) {
            return document.createElementNS('http://www.w3.org/2000/svg', tagName);
        },

        handleDragStart(event) {
            this.DnDAllow = false;

            IconTypeView.__super__.handleDragStart.call(this, event);
        },

        onMouseDown() {
            this.DnDAllow = true;
        },

        onMouseMove(event) {
            if (!this.DnDAllow) {
                return;
            }

            if (event.movementX > 0 || event.movementY > 0) {
                this.handleDragStart(event);
            }
        },

        onMouseUp() {
            this.DnDAllow = false;
        },

        onRender() {
            this.renderIcon(this.model.get('iconId'));
        },

        onActive(event) {
            event && event.stopPropagation();

            const dialog = editor.Commands.run('component:settings-dialog');
            !event && dialog.once('cancel', () => {
                editor.selectRemove(this.model);
                this.model.remove();
            });
        },

        onIconUpdate(model, iconId) {
            this.renderIcon(iconId);
            this.model.setAttributes({
                'data-init-icon': iconId
            });
        },

        renderIcon(iconId) {
            const currentTheme = editor.em.get('currentTheme');
            const {IconsService} = editor;

            if (IconsService.isSvgIconsSupport(currentTheme) === false) {
                this.$el.html(this.noIconPlaceholder);
                return;
            }

            const {name} = currentTheme;
            this.$el.html(`<use href="${IconsService.getSvgIconUrl(iconId, name)}"></use>`);

            IconsService.isIconAvailable({iconId, theme: {name}}).then(isAveabile => {
                if (!isAveabile) {
                    this.$el.html(this.noIconPlaceholder);
                }
            });
        }
    });

    return IconTypeView;
};
