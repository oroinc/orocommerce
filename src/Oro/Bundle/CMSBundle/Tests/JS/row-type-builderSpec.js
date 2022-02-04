import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import RowTypeBuilder from 'orocms/js/app/grapesjs/type-builders/row-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/row-type-builder', () => {
    let rowTypeBuilder;
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

    describe('component "RowTypeBuilder"', () => {
        beforeEach(() => {
            rowTypeBuilder = new RowTypeBuilder({
                editor,
                componentType: 'row'
            });

            rowTypeBuilder.execute();
        });

        afterEach(() => {
            rowTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(rowTypeBuilder.componentType).toEqual('row');
        });

        it('check is component type defined', () => {
            const type = rowTypeBuilder.editor.DomComponents.getType('row');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'row'
            }));
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.classList.add('grid-row');

            expect(rowTypeBuilder.Model.isComponent).toBeDefined();
            expect(rowTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: rowTypeBuilder.componentType
            });

            expect(rowTypeBuilder.Model.componentType).toEqual(rowTypeBuilder.componentType);
            expect(rowTypeBuilder.Model.prototype.defaults.classes).toEqual(['grid-row']);
            expect(rowTypeBuilder.Model.prototype.defaults.droppable).toEqual('.grid-cell');
            expect(rowTypeBuilder.Model.prototype.defaults.resizable).toEqual({
                tl: 0,
                tc: 0,
                tr: 0,
                cl: 0,
                cr: 0,
                bl: 0,
                br: 0,
                minDim: 50
            });

            expect(rowTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor events', () => {
            expect(rowTypeBuilder.editorEvents['selector:add']).toBeDefined();
        });
    });
});
