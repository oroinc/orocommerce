import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkButtonTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-button-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/link-block-builder', () => {
    let linkButtonTypeBuilder;
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

    describe('component "LinkButtonTypeBuilder"', () => {
        beforeEach(() => {
            linkButtonTypeBuilder = new LinkButtonTypeBuilder({
                editor,
                componentType: 'link-button'
            });

            linkButtonTypeBuilder.execute();
        });

        afterEach(() => {
            linkButtonTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(linkButtonTypeBuilder).toBeDefined();
            expect(linkButtonTypeBuilder.componentType).toEqual('link-button');
        });

        it('check is component type defined', () => {
            const type = linkButtonTypeBuilder.editor.DomComponents.getType('link-button');
            expect(type).toBeDefined();
            expect(type.id).toEqual('link-button');
        });

        it('check is component type button', () => {
            const button = linkButtonTypeBuilder.editor.BlockManager.get(linkButtonTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check component parent type', () => {
            expect(linkButtonTypeBuilder.parentType).toEqual('link');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');
            mockElement.classList.add('btn');

            expect(linkButtonTypeBuilder.Model.isComponent).toBeDefined();
            expect(linkButtonTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: linkButtonTypeBuilder.componentType
            });
            expect(linkButtonTypeBuilder.Model.componentType).toEqual(linkButtonTypeBuilder.componentType);

            expect(linkButtonTypeBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(linkButtonTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['btn', 'btn--info']
            );

            expect(linkButtonTypeBuilder.Model.prototype.editor).toBeDefined();
            expect(linkButtonTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
