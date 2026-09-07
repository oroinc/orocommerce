import 'jasmine-jquery';
import DialogWidget from 'orowindows/js/widget/dialog-widget';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {installDialogEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/dialog-embedded-extend';
import html from 'text-loader!../Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/patches/dialog-embedded-extend', () => {
    const proto = DialogWidget.prototype;

    let originalInitialize;
    let initialize;
    let widget;
    let fragment;

    beforeEach(() => {
        window.setFixtures(html);

        originalInitialize = proto.initialize;
        initialize = jasmine.createSpy('initialize');
        proto.initialize = initialize;
        installDialogEmbeddedExtend();

        widget = {options: {dialogOptions: {modal: true, width: 500, resizable: false}}};
        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay')
        };
    });

    afterEach(() => {
        proto.initialize = originalInitialize;
        clearActiveFragment(fragment);
    });

    const openDialog = options => proto.initialize.call(widget, options);

    it('appends the dialog to the fragment overlay', () => {
        setActiveFragment(fragment);

        openDialog({title: 'Shopping lists'});

        const [options] = initialize.calls.mostRecent().args;
        expect(options.dialogOptions.appendTo).toBe(fragment.overlayRoot);
        expect(options.incrementalPosition).toBe(false);
    });

    it('keeps the dialog options of the widget class the caller omitted', () => {
        setActiveFragment(fragment);

        openDialog({title: 'Shopping lists'});

        const [options] = initialize.calls.mostRecent().args;
        expect(options.dialogOptions.modal).toBe(true);
        expect(options.dialogOptions.width).toBe(500);
        expect(options.dialogOptions.resizable).toBe(false);
    });

    it('keeps the dialog options given by the caller', () => {
        setActiveFragment(fragment);

        openDialog({dialogOptions: {modal: false, width: 300}});

        const [options] = initialize.calls.mostRecent().args;
        expect(options.dialogOptions.appendTo).toBe(fragment.overlayRoot);
        expect(options.dialogOptions.modal).toBe(false);
        expect(options.dialogOptions.width).toBe(300);
    });

    it('leaves the options unchanged when no fragment is active', () => {
        openDialog({title: 'Not embedded'});

        const [options] = initialize.calls.mostRecent().args;
        expect(options.dialogOptions).toBeUndefined();
        expect(options.incrementalPosition).toBeUndefined();
    });

    it('initializes a dialog called without options', () => {
        setActiveFragment(fragment);

        openDialog();

        expect(initialize).toHaveBeenCalled();
    });
});
