import 'jasmine-jquery';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import Modal from 'oroui/js/modal';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import '../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/plugins/code-validator', () => {
    let grapesjsEditorView;
    let codeValidator;

    beforeEach(done => {
        window.setFixtures(html);
        grapesjsEditorView = new GrapesjsEditorView({
            el: '#grapesjs-view',
            themes: [{
                label: 'Test',
                stylesheet: '',
                active: true
            }],
            disableDeviceManager: true
        });

        codeValidator = grapesjsEditorView.builder.CodeValidator;

        grapesjsEditorView.builder.on('editor:rendered', () => done());
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('feature "CodeValidator"', () => {
        beforeEach(() => {
            codeValidator.invalid = false;
            codeValidator.$textInputElement.val(`<div>
                <div id="test"></div>
                <div id="test"></div>
            </div>`);
            codeValidator.$stylesInputElement.val(`#test {color: red;}`);
        });

        afterEach(() => {
            codeValidator.invalid = false;
            codeValidator.unLockEditor();
            codeValidator.$textInputElement.val('');
        });

        it('check editor defined', () => {
            expect(codeValidator.editor).toEqual(grapesjsEditorView.builder);
        });

        it('check lock editor', () => {
            codeValidator.invalid = true;
            codeValidator.lockEditor();

            expect(codeValidator.lockOverlay).toBeInstanceOf(Modal);
        });

        it('check unlock editor', () => {
            codeValidator.invalid = false;
            codeValidator.unLockEditor();

            expect(codeValidator.lockOverlay).toBeUndefined();
        });

        it('check validator', () => {
            codeValidator.validate(`<div>
                <div id="test"></div>
                <div id="test"></div>
            </div>`);

            expect(codeValidator.invalid).toBe(true);
            expect(codeValidator.lockOverlay).toBeInstanceOf(Modal);
        });

        it('check "contentValidate"', () => {
            expect(codeValidator.contentValidate().length).toBeGreaterThan(0);
        });

        it('check "getRawContent"', () => {
            expect(codeValidator.getRawContent()).toEqual(`<div>
                <div id="test"></div>
                <div id="test"></div>
            </div>
            <style>#test {color: red;}</style>`);
        });
    });
});
