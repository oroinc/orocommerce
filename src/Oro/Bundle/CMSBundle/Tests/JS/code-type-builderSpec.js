import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import CodeTypeBuilder from 'orocms/js/app/grapesjs/type-builders/code-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/code-type-builder', () => {
    let codeTypeBuilder;
    let editor;

    beforeEach(() => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor')
        });

        editor.ComponentRestriction = new ComponentRestriction(editor, {});
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
            expect(codeTypeBuilder.template()).toEqual('<pre>oro.cms.wysiwyg.component.code.placeholder</pre>');
        });

        it('check component parent type', () => {
            expect(codeTypeBuilder.parentType).toEqual('text');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('PRE');

            expect(codeTypeBuilder.Model.isComponent).toBeDefined();
            expect(codeTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: codeTypeBuilder.componentType
            });
            expect(codeTypeBuilder.Model.componentType).toEqual(codeTypeBuilder.componentType);

            expect(codeTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
