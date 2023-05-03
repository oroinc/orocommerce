import 'jasmine-jquery';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import 'oroui/js/app/modules/layout-module';
import '../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/types/custom-code-type', () => {
    let grapesjsEditorView;
    let customCodeTypeBuilder;
    let editor;

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

        editor = grapesjsEditorView.builder;
        grapesjsEditorView.builder.on('editor:rendered', () => done());
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('component "CustomCodeTypeBuilder"', () => {
        beforeEach(() => {
            customCodeTypeBuilder = editor.componentManager.getTypeBuilder('custom-code');
        });

        it('check to be defined', () => {
            expect(customCodeTypeBuilder).toBeDefined();
            expect(customCodeTypeBuilder.componentType).toEqual('custom-code');
        });

        it('check is component type defined', () => {
            const type = customCodeTypeBuilder.editor.DomComponents.getType('custom-code');
            expect(type).toBeDefined();
            expect(type.id).toEqual('custom-code');
        });

        it('check is component type button', () => {
            const button = customCodeTypeBuilder.editor.BlockManager.get(customCodeTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.setAttribute('data-type', 'custom-source-code');
            mockElement.innerHTML = '<div><h1>Heading 1</h1></div>';

            expect(customCodeTypeBuilder.Model.isComponent).toBeDefined();
            expect(customCodeTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: customCodeTypeBuilder.componentType,
                content: '<div><h1>Heading 1</h1></div>'
            });

            expect(customCodeTypeBuilder.Model.componentType).toEqual(customCodeTypeBuilder.componentType);

            expect(customCodeTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(customCodeTypeBuilder.Model.prototype.defaults.type).toEqual('custom-code');
            expect(customCodeTypeBuilder.Model.prototype.defaults.copyable).toBe(false);
            expect(customCodeTypeBuilder.Model.prototype.defaults.stylable).toBe(false);
            expect(customCodeTypeBuilder.Model.prototype.defaults.droppable).toBe(false);
            expect(customCodeTypeBuilder.Model.prototype.defaults.traits).toEqual([]);
            expect(customCodeTypeBuilder.Model.prototype.defaults.disableSelectorManager).toBe(true);

            expect(customCodeTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        xdescribe('test type in editor scope', () => {
            let customCodeComponent;
            beforeEach(done => {
                window.nodeType = 4;
                editor.setComponents(
                    '<div data-type="custom-source-code" id="test"><div id="test-2"><h1>Heading 1</h1></div></div>'
                );

                customCodeComponent = editor.Components.getComponents().models[0];
                editor.select(customCodeComponent);
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.getSelectedAll().forEach(selected => editor.selectRemove(selected));
                setTimeout(() => {
                    editor.setComponents('');
                    done();
                }, 10);
            });

            it('is defined', () => {
                expect(customCodeComponent).toBeDefined();
                expect(customCodeComponent.get('tagName')).toEqual('div');
                expect(customCodeComponent.get('content')).toEqual('<div id="test-2"><h1>Heading 1</h1></div>');
            });

            it('check toolbar', () => {
                const toolbar = customCodeComponent.get('toolbar');
                expect(toolbar.length).toEqual(5);
                expect(toolbar[0].id).toEqual('edit-custom-code');
                expect(toolbar[1].id).toEqual('expose-custom-code');
            });
        });
    });
});
