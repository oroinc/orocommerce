import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import GridRowTypeBuilder from 'orocms/js/app/grapesjs/type-builders/grid-row-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/grid-row-type-builder', () => {
    let gridRowTypeBuilder;
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
            gridRowTypeBuilder = new GridRowTypeBuilder({
                editor,
                componentType: 'grid-row'
            });

            gridRowTypeBuilder.execute();
        });

        afterEach(() => {
            gridRowTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(gridRowTypeBuilder.componentType).toEqual('grid-row');
        });

        it('check is component type defined', () => {
            const type = gridRowTypeBuilder.editor.DomComponents.getType('grid-row');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'grid-row'
            }));
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.classList.add('grid-row');

            expect(gridRowTypeBuilder.Model.isComponent).toBeDefined();
            expect(gridRowTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: gridRowTypeBuilder.componentType
            });

            expect(gridRowTypeBuilder.Model.componentType).toEqual(gridRowTypeBuilder.componentType);
            expect(gridRowTypeBuilder.Model.prototype.defaults.classes).toEqual(['grid-row']);
            expect(gridRowTypeBuilder.Model.prototype.defaults.droppable).toEqual('.grid-cell');
            expect(gridRowTypeBuilder.Model.prototype.defaults.resizable).toEqual({
                tl: 0,
                tc: 0,
                tr: 0,
                cl: 0,
                cr: 0,
                bl: 0,
                br: 0,
                minDim: 50
            });

            expect(gridRowTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor events', () => {
            expect(gridRowTypeBuilder.editorEvents['selector:add']).toBeDefined();
        });
    });
});
