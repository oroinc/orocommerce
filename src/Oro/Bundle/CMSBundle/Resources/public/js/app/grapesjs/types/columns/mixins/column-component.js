export default {
    storedClasses: {},

    getSpan() {
        const span = this.getStyle('--grid-column-span');

        if (!span) {
            return this.view.getComputedSpan();
        }

        return span;
    },

    setSpan(toSet) {
        this.setStyle({
            '--grid-column-span': toSet
        });
    },

    updateClasses({__clear, namespace = '', devices = true, replace = true} = {}) {
        const {Devices, Selectors} = this.editor;
        const classes = this.get('classes');
        const currentDevice = devices ? Devices.getSelected() : false;
        let breakpoint = currentDevice ? currentDevice.get('id') : '';

        if (breakpoint === 'desktop') {
            breakpoint = '';
        }

        if (__clear) {
            const stored = this.storedClasses[`${namespace}-${breakpoint}`];
            stored && Selectors.addSelected(stored);
            return;
        }

        const found = classes.find(
            cls => new RegExp(`${namespace}-${breakpoint}[\\-\\d]+`, 'g').test(cls.get('name'))
        );

        if (found) {
            this.storedClasses = {
                ...this.storedClasses,
                [`${namespace}-${breakpoint}`]: found
            };

            Selectors.removeSelected(found);

            if (!this.getClasses().includes(namespace) && replace) {
                this.addClass(namespace);
                classes.forEach(
                    cls => this.get('privateClasses').includes(cls.get('name')) && cls.set('private', true)
                );
            }
        }
    },

    getCodeModeState() {
        const state = this.editor.getState();

        return state.get('codeMode') || false;
    }
};
