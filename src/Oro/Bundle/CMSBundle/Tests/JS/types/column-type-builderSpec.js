import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ColumnTypeBuilder from 'orocms/js/app/grapesjs/type-builders/column-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/column-type-builder', () => {
    let columnTypeBuilder;
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

    describe('component "ColumnTypeBuilder"', () => {
        beforeEach(() => {
            columnTypeBuilder = new ColumnTypeBuilder({
                editor,
                componentType: 'column'
            });

            columnTypeBuilder.execute();
        });

        afterEach(() => {
            columnTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(columnTypeBuilder.componentType).toEqual('column');
        });

        it('check is component type defined', () => {
            const type = columnTypeBuilder.editor.DomComponents.getType('column');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'column'
            }));
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.classList.add('grid-cell');

            expect(columnTypeBuilder.Model.isComponent).toBeDefined();
            expect(columnTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: columnTypeBuilder.componentType
            });

            expect(columnTypeBuilder.Model.componentType).toEqual(columnTypeBuilder.componentType);
            expect(columnTypeBuilder.Model.prototype.defaults.classes).toEqual(['grid-cell']);
            expect(columnTypeBuilder.Model.prototype.defaults.draggable).toEqual('.grid-row');
            expect(columnTypeBuilder.Model.prototype.defaults.resizable).toEqual({
                tl: 0,
                tc: 0,
                tr: 0,
                bl: 0,
                br: 0,
                bc: 0,
                minDim: 25,
                maxDim: 75,
                step: 0.2,
                currentUnit: 0,
                unitWidth: '%'
            });

            expect(columnTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor events', () => {
            expect(columnTypeBuilder.editorEvents['selector:add']).toBeDefined();
        });
    });
});
