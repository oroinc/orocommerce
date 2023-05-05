import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import GridTypeBuilder from 'orocms/js/app/grapesjs/types/grid-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/grid-type', () => {
    let gridTypeBuilder;
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

    describe('component "GridTypeBuilder"', () => {
        beforeEach(() => {
            gridTypeBuilder = new GridTypeBuilder({
                editor,
                componentType: 'grid'
            });

            gridTypeBuilder.editor.BlockManager.add('column1', {
                label: 'Column1'
            });
            gridTypeBuilder.editor.BlockManager.add('column2', {
                label: 'Column2'
            });
            gridTypeBuilder.editor.BlockManager.add('column3', {
                label: 'Column3'
            });
            gridTypeBuilder.editor.BlockManager.add('column3-7', {
                label: 'Column3-7'
            });

            gridTypeBuilder.execute();
        });

        afterEach(() => {
            gridTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(gridTypeBuilder.componentType).toEqual('grid');
        });

        it('check is component type button', () => {
            const buttonColumn1 = gridTypeBuilder.editor.BlockManager.get('column1');
            const buttonColumn2 = gridTypeBuilder.editor.BlockManager.get('column2');
            const buttonColumn3 = gridTypeBuilder.editor.BlockManager.get('column3');
            const buttonColumn37 = gridTypeBuilder.editor.BlockManager.get('column3-7');
            expect(buttonColumn1).toBeDefined();
            expect(buttonColumn2).toBeDefined();
            expect(buttonColumn3).toBeDefined();
            expect(buttonColumn37).toBeDefined();
            expect(buttonColumn1.get('content')).toEqual(jasmine.any(String));
            expect(buttonColumn2.get('content')).toEqual(jasmine.any(String));
            expect(buttonColumn3.get('content')).toEqual(jasmine.any(String));
            expect(buttonColumn37.get('content')).toEqual(jasmine.any(String));
        });
    });
});
