import __ from 'orotranslation/js/translator';

export default (BaseTypeModel, {editor}) => {
    const LinkButtonTypeModel = BaseTypeModel.extend({
        editor,

        constructor: function LinkButtonTypeModel(...args) {
            return LinkButtonTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            LinkButtonTypeModel.__super__.init.call(this);

            this.listenTo(this, 'change:iconEnabled', this.onIconEnabledToggle);
            this.listenTo(this, 'change:iconBefore', (model, value) => {
                const [icon] = this.findType('icon');
                if (icon) {
                    value ? this.append(icon, {at: 0}) : this.append(icon);
                }
            });

            const [icon] = this.findType('icon');
            this.icon = icon;

            if (this.icon) {
                this.icon.set({
                    draggable: false,
                    copyable: false,
                    removable: false
                });
                this.icon.initToolbar({reset: true});
            }

            this.set('iconEnabled', this.icon);
            this.set('iconBefore', this.getChildAt(0).is('icon'));

            const traitText = this.getTrait('text');
            const [textnode] = this.findType('textnode');

            if (traitText && textnode) {
                traitText.set('value', textnode.get('content'));
            }
        },

        onIconEnabledToggle(model, value) {
            if (value) {
                if (!this.icon) {
                    this.icon = this.append({
                        type: 'icon',
                        tagName: 'svg',
                        iconId: 'add-note',
                        draggable: false,
                        copyable: false,
                        removable: false
                    })[0];
                }

                this.addTrait({
                    id: 'iconBefore',
                    name: 'iconBefore',
                    type: 'checkbox',
                    label: __('oro.cms.wysiwyg.component.link_button.icon_before'),
                    changeProp: true
                });
            } else {
                this.icon && this.icon.remove();
                this.icon = null;
                this.removeTrait('iconBefore');
            }
        }
    });

    Object.defineProperty(LinkButtonTypeModel.prototype, 'defaults', {
        value: {
            ...LinkButtonTypeModel.prototype.defaults,
            tagName: 'a',
            classes: ['btn', 'btn--outlined'],
            style: {},
            components: [{
                type: 'textnode',
                content: __('oro.cms.wysiwyg.component.link_button.content')
            }],
            traits: [...LinkButtonTypeModel.prototype.defaults.traits, {
                type: 'checkbox',
                name: 'iconEnabled',
                label: __('oro.cms.wysiwyg.component.link_button.icon_enabled'),
                changeProp: true
            }]
        }
    });

    return LinkButtonTypeModel;
};
