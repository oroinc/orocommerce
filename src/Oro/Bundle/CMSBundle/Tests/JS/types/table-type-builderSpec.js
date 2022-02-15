import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import TableTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/table-type-builder', () => {
    let tableTypeBuilder;
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

    describe('component "TableTypeBuilder"', () => {
        beforeEach(() => {
            tableTypeBuilder = new TableTypeBuilder({
                editor,
                componentType: 'table'
            });

            tableTypeBuilder.execute();
        });

        afterEach(() => {
            tableTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(tableTypeBuilder.componentType).toEqual('table');
        });

        it('check is component type defined', () => {
            const type = tableTypeBuilder.editor.DomComponents.getType('table');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'table'
            }));
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('TABLE');

            expect(tableTypeBuilder.Model.isComponent).toBeDefined();
            expect(tableTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: tableTypeBuilder.componentType
            });

            expect(tableTypeBuilder.Model.componentType).toEqual(tableTypeBuilder.componentType);
            expect(tableTypeBuilder.Model.prototype.defaults.tagName).toEqual('table');
            expect(tableTypeBuilder.Model.prototype.defaults.classes).toEqual(['table']);
            expect(tableTypeBuilder.Model.prototype.defaults.draggable).toEqual(['div']);
            expect(tableTypeBuilder.Model.prototype.defaults.droppable).toEqual(['tbody', 'thead', 'tfoot']);

            expect(tableTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let tableComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'table'
                }]);

                tableComponent = editor.Components.getComponents().models[0];

                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(tableComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<table class="table"><thead><tr class="row"><td class="cell"></td></tr></thead><tbody><tr class="row"><td class="cell"></td></tr></tbody><tfoot><tr class="row"><td class="cell"></td></tr></tfoot></table>'
                );
            });
        });
    });
});
