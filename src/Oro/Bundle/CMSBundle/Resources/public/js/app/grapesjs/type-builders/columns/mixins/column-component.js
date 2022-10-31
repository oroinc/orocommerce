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

    updateClasses({__clear}) {
        const {Devices, Selectors} = this.editor;
        const classes = this.get('classes');
        const currentDevice = Devices.getSelected();
        let breakpoint = currentDevice ? currentDevice.get('id') : '';

        if (breakpoint === 'desktop') {
            breakpoint = '';
        }

        if (__clear) {
            const stored = this.storedClasses[`grid-col-${breakpoint}`];
            stored && Selectors.addSelected(stored);
            return;
        }

        const found = classes.find(
            cls => new RegExp(`grid-col-${breakpoint}[\\-\\d]+`, 'g').test(cls.get('name'))
        );

        if (found) {
            this.storedClasses = {
                ...this.storedClasses,
                [`grid-col-${breakpoint}`]: found
            };

            Selectors.removeSelected(found);

            if (!this.getClasses().includes('grid-col')) {
                this.addClass('grid-col');
                classes.forEach(
                    cls => this.get('privateClasses').includes(cls.get('name')) && cls.set('private', true)
                );
            }
        }
    }
};
