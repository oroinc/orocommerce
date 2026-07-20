export default function dividerTraitInit({editor}) {
    const ppfx = editor.getConfig('stylePrefix') || 'gjs-';

    editor.Traits.addType('divider', {
        noLabel: true,
        templateInput: '',

        createInput() {
            const el = document.createElement('div');

            el.classList.add(`${ppfx}trait-divider`);

            return el;
        }
    });
}
