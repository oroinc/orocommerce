export default function iconIdTraitInit({editor}) {
    editor.Traits.addType('icon-id', {
        clsField: '',

        createInput({component, trait, elInput}) {
            const iconsSettings = component.settings.find(({name}) => name === 'iconId');
            const el = document.createElement('div');

            this.icons = iconsSettings.getView({
                el,
                searchFieldCls: 'gjs-field gjs-field-text',
                showLabel: false,
                traitMode: true
            });

            this.listenTo(this.icons, 'selected', iconId => trait.setValue(iconId));
            this.listenTo(component, `change:${trait.get('name')}`, (_, value) => this.icons.setValue(value));

            return el;
        },

        onUpdate({trait}) {
            this.icons?.setValue(trait.getValue());
        },

        init() {
            const {model} = this;

            model.set({
                id: 'iconId',
                name: 'iconId',
                label: 'Icon Id'
            });
        }
    });
};
