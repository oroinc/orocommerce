import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import CodeTypeBuilder from 'orocms/js/app/grapesjs/types/code-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/code-type', () => {
    let codeTypeBuilder;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            deviceManager: {
                devices: []
            }
        });

        editor.ComponentRestriction = new ComponentRestriction(editor, {});

        editor.on('load', () => done());
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('component "CodeTypeBuilder"', () => {
        beforeEach(() => {
            codeTypeBuilder = new CodeTypeBuilder({
                editor,
                componentType: 'code'
            });

            codeTypeBuilder.execute();
        });

        afterEach(() => {
            codeTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(codeTypeBuilder.componentType).toEqual('code');
        });

        it('check is component type defined', () => {
            const type = codeTypeBuilder.editor.DomComponents.getType('code');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'code'
            }));
        });

        it('check is component type button', () => {
            const button = codeTypeBuilder.editor.BlockManager.get(codeTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check template', () => {
            expect(codeTypeBuilder.template()).toEqual(
                '<pre><code>oro.cms.wysiwyg.component.code.placeholder</code></pre>'
            );
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('PRE');
            mockElement.innerHTML = '<code>Code</code>';

            expect(codeTypeBuilder.Model.isComponent).toBeDefined();
            expect(codeTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(codeTypeBuilder.Model.componentType).toEqual(codeTypeBuilder.componentType);

            expect(codeTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
