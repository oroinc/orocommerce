import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import TableResponsiveTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-responsive-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/table-responsive-type-builder', () => {
    let tableResponsiveTypeBuilder;
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

    describe('component "TableResponsiveTypeBuilder"', () => {
        beforeEach(() => {
            tableResponsiveTypeBuilder = new TableResponsiveTypeBuilder({
                editor,
                componentType: 'table-responsive'
            });

            tableResponsiveTypeBuilder.execute();
        });

        afterEach(() => {
            tableResponsiveTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(tableResponsiveTypeBuilder.componentType).toEqual('table-responsive');
        });

        it('check is component type defined', () => {
            const type = tableResponsiveTypeBuilder.editor.DomComponents.getType('table-responsive');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'table-responsive'
            }));
        });

        it('check is component type button', () => {
            const button = tableResponsiveTypeBuilder.editor.BlockManager.get(tableResponsiveTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.classList.add('table-responsive');

            expect(tableResponsiveTypeBuilder.Model.isComponent).toBeDefined();
            expect(tableResponsiveTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: tableResponsiveTypeBuilder.componentType
            });

            expect(tableResponsiveTypeBuilder.Model.componentType).toEqual(tableResponsiveTypeBuilder.componentType);
            expect(tableResponsiveTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(tableResponsiveTypeBuilder.Model.prototype.defaults.classes).toEqual(['table-responsive']);
            expect(tableResponsiveTypeBuilder.Model.prototype.defaults.draggable).toEqual(['div']);
            expect(
                tableResponsiveTypeBuilder.Model.prototype.defaults.droppable
            ).toEqual(['table', 'tbody', 'thead', 'tfoot']);

            expect(tableResponsiveTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
