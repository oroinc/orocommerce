import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import TableResponsiveTypeBuilder from 'orocms/js/app/grapesjs/types/table-responsive-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/table-responsive-type', () => {
    let tableResponsiveTypeBuilder;
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
            expect(tableResponsiveTypeBuilder.Model.isComponent(mockElement)).toBe(true);

            expect(tableResponsiveTypeBuilder.Model.componentType).toEqual(tableResponsiveTypeBuilder.componentType);
            expect(tableResponsiveTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(tableResponsiveTypeBuilder.Model.prototype.defaults.classes).toEqual(['table-responsive']);
            expect(
                tableResponsiveTypeBuilder.Model.prototype.defaults.droppable
            ).toEqual(['table']);

            expect(tableResponsiveTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let tableResponsiveComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'table-responsive'
                }]);

                tableResponsiveComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(tableResponsiveComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<div class="table-responsive"><table><tbody><tr class="row"><td class="cell"></td></tr></tbody></table></div>'
                );
            });
        });
    });
});
