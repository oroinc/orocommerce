import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import GridColumnTypeBuilder from 'orocms/js/app/grapesjs/types/grid-column-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/column-type', () => {
    let gridColumnTypeBuilder;
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

    describe('component "ColumnTypeBuilder"', () => {
        beforeEach(() => {
            gridColumnTypeBuilder = new GridColumnTypeBuilder({
                editor,
                componentType: 'grid-column'
            });

            gridColumnTypeBuilder.execute();
        });

        afterEach(() => {
            gridColumnTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(gridColumnTypeBuilder.componentType).toEqual('grid-column');
        });

        it('check is component type defined', () => {
            const type = gridColumnTypeBuilder.editor.DomComponents.getType('grid-column');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'grid-column'
            }));
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('DIV');
            mockElement.classList.add('grid-cell');

            expect(gridColumnTypeBuilder.Model.isComponent).toBeDefined();
            expect(gridColumnTypeBuilder.Model.isComponent(mockElement)).toBe(true);

            expect(gridColumnTypeBuilder.Model.componentType).toEqual(gridColumnTypeBuilder.componentType);
            expect(gridColumnTypeBuilder.Model.prototype.defaults.classes).toEqual(['grid-cell']);
            expect(gridColumnTypeBuilder.Model.prototype.defaults.draggable).toEqual('.grid-row');
            expect(gridColumnTypeBuilder.Model.prototype.defaults.resizable).toEqual({
                tl: 0,
                tc: 0,
                tr: 0,
                bl: 0,
                br: 0,
                bc: 0,
                minDim: 10,
                maxDim: 90,
                step: 0.2,
                currentUnit: 0,
                unitWidth: '%',
                updateTarget: jasmine.any(Function)
            });

            expect(gridColumnTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor events', () => {
            expect(gridColumnTypeBuilder.editorEvents['selector:add']).toBeDefined();
        });
    });
});
