import 'jasmine-jquery';
import ImportDialogView from 'orocms/js/app/grapesjs/plugins/import/import-dialog-view';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import '../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/plugins/import/import-dialog-view', () => {
    let editor;
    let grapesjsEditorView;

    beforeEach(done => {
        window.setFixtures(html);
        window.nodeType = 1;
        grapesjsEditorView = new GrapesjsEditorView({
            el: '#grapesjs-view',
            themes: [{
                label: 'Test',
                stylesheet: '',
                active: true
            }],
            disableDeviceManager: true
        });

        grapesjsEditorView.builder.on('editor:rendered', () => done());
        editor = grapesjsEditorView.builder;
        editor.on('load', () => editor.setComponents(
            '<div class="test">Test content</div><style>.test {color: red;}</style>'
        ));
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('module "ImportDialogView"', () => {
        let importDialogView;

        beforeEach(() => {
            importDialogView = new ImportDialogView({
                editor,
                autoRender: false
            });

            importDialogView.dialog = {
                remove() {},
                resetDialogPosition() {},
                widget: {
                    width() {},
                    height() {}
                }
            };
            importDialogView.viewerEditor = {
                off() {},
                getValue() {
                    return '<div class="test">New test content</div><style>.test {color: green;}</style>';
                },
                setSize() {}
            };
            importDialogView.importButton = {
                off() {}
            };
        });

        afterEach(() => {
            importDialogView.dispose();
        });

        it('check "getImportContent"', () => {
            expect(importDialogView.getImportContent()).toEqual(
                // eslint-disable-next-line
                '<div class="test">Test content</div><style>.test{color:red;}</style>'
            );
        });

        it('check "onImportCode"', () => {
            importDialogView.onImportCode();
            expect(editor.getHtml()).toEqual('<div class="test">New test content</div>');
            expect(editor.getCss()).toEqual('.test{color:green;}');
        });

        it('check "isChange"', () => {
            expect(importDialogView.isChange()).toBe(true);
        });

        it('check "validationMessage"', () => {
            importDialogView.$el.append('<div class="validation-failed"></div>');

            let messages = [
                'Test message',
                'Some test message'
            ];
            importDialogView.validationMessage(messages.join('\n'));

            expect(importDialogView.$el.find('.validation-failed').html()).toEqual(`Test message
Some test message`);

            messages = [
                'Test message <a href="#">Test link</a>',
                'Some test message <a href="#">Test link</a>'
            ];

            importDialogView.validationMessage(messages.join('\n'));

            expect(
                importDialogView.$el.find('.validation-failed').html()
            ).toEqual(
                // eslint-disable-next-line
                `Test message &lt;a href="#"&gt;Test link&lt;/a&gt;
Some test message &lt;a href="#"&gt;Test link&lt;/a&gt;`
            );
        });
    });
});
