import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/link-type-builder', () => {
    let linkTypeBuilder;
    let editor;

    beforeEach(() => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor')
        });
        editor.BlockManager.add('link', {
            label: 'Test Link',
            content: '<a href="#">Test link</a>',
            category: 'Basic'
        });
        editor.ComponentRestriction = new ComponentRestriction(editor, {});
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('component "LinkTypeBuilder"', () => {
        beforeEach(() => {
            linkTypeBuilder = new LinkTypeBuilder({
                editor,
                componentType: 'link'
            });

            linkTypeBuilder.execute();
        });

        afterEach(() => {
            linkTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(linkTypeBuilder).toBeDefined();
            expect(linkTypeBuilder.componentType).toEqual('link');
        });

        it('check is component type defined', () => {
            const type = linkTypeBuilder.editor.DomComponents.getType('link');
            expect(type).toBeDefined();
            expect(type.id).toEqual('link');
        });

        it('check is component type button', () => {
            const button = linkTypeBuilder.editor.BlockManager.get(linkTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check editor commands defined', () => {
            expect(linkTypeBuilder.editor.Commands.get('open-create-link-dialog')).toBeDefined();
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');

            expect(linkTypeBuilder.Model.isComponent).toBeDefined();
            expect(linkTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: linkTypeBuilder.componentType
            });
            expect(linkTypeBuilder.Model.componentType).toEqual(linkTypeBuilder.componentType);

            expect(linkTypeBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(linkTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['link']
            );
            expect(linkTypeBuilder.Model.prototype.defaults.traits).toEqual(['href', 'text', 'title', 'target']);
            expect(linkTypeBuilder.Model.prototype.defaults.components.length).toEqual(1);
            expect(linkTypeBuilder.Model.prototype.defaults.editable).toBeFalsy();
            expect(linkTypeBuilder.Model.prototype.defaults.droppable).toBeTruthy();

            expect(linkTypeBuilder.Model.prototype.editor).toBeDefined();
            expect(linkTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
