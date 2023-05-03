import 'jasmine-jquery';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import TableModify from 'orocms/js/app/grapesjs/controls/table-edit/table-modify';
import html from 'text-loader!../../fixtures/grapesjs-editor-view-fixture.html';
import '../../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/controls/table-edit', () => {
    let grapesjsEditorView;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        grapesjsEditorView = new GrapesjsEditorView({
            el: '#grapesjs-view',
            themes: [{
                label: 'Test',
                stylesheet: '',
                active: true
            }],
            disableDeviceManager: true
        });

        grapesjsEditorView.builder.on('editor:rendered', () => done());

        editor = grapesjsEditorView.builder;
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('check table', () => {
        let tableComponent;
        let tableModify;
        beforeEach(done => {
            editor.setComponents([{
                type: 'table'
            }]);

            tableComponent = editor.Components.getComponents().models[0];
            tableModify = new TableModify(tableComponent);
            setTimeout(() => done(), 0);
        });

        afterEach(done => {
            editor.setComponents([]);
            setTimeout(() => done(), 0);
        });

        it('add rows', () => {
            const cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];
            cell.setClass('test');

            expect(tableComponent.find('tr').length).toEqual(2);
            tableModify.modify('insert-row-after', {
                selected: cell
            });

            expect(tableComponent.find('tr').length).toEqual(3);
            tableModify.modify('insert-row-before', {
                selected: cell
            });

            expect(tableComponent.find('tr').length).toEqual(4);

            expect(tableComponent.toHTML()).toEqual(
                // eslint-disable-next-line
                `<table class="table"><thead><tr><th><div>oro.cms.wysiwyg.component.table.header_cell_label</div></th></tr><tr class="row"><td class="test"><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td></tr><tr><th><div>oro.cms.wysiwyg.component.table.header_cell_label</div></th></tr></thead><tbody><tr class="row"><td class="cell"><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td></tr></tbody></table>`
            );
        });

        it('add columns', () => {
            const cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];
            cell.setClass('test');

            expect(tableComponent.find('tr:nth-child(1) > td').length).toEqual(2);
            tableModify.modify('insert-column-after', {
                selected: cell
            });

            expect(tableComponent.find('tr:nth-child(1) > td').length).toEqual(3);
            tableModify.modify('insert-column-before', {
                selected: cell
            });

            expect(tableComponent.find('tr:nth-child(1) > td').length).toEqual(4);

            expect(tableComponent.toHTML()).toEqual(
                // eslint-disable-next-line
                `<table class="table"><thead><tr class="row"><th><div>oro.cms.wysiwyg.component.table.header_cell_label</div></th><td class="test"><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td><th><div>oro.cms.wysiwyg.component.table.header_cell_label</div></th></tr></thead><tbody><tr class="row"><td><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td><td class="cell"><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td><td><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td></tr></tbody></table>`
            );
        });

        it('remove row', () => {
            expect(tableComponent.find('tr').length).toEqual(2);

            let cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];
            tableModify.modify('delete-row', {
                selected: cell
            });

            expect(tableComponent.find('tr').length).toEqual(1);

            cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];
            tableModify.modify('delete-row', {
                selected: cell
            });

            expect(tableComponent.find('tr').length).toEqual(0);

            expect(tableComponent.toHTML()).toEqual(`<table class="table"><tbody></tbody></table>`);

            setTimeout(() => {
                expect(editor.getHtml()).toEqual('');
            });
        });

        it('remove column', () => {
            expect(tableComponent.find('td').length).toEqual(2);

            const cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];
            tableModify.modify('delete-column', {
                selected: cell
            });

            expect(tableComponent.find('td').length).toEqual(0);

            expect(tableComponent.toHTML()).toEqual(
                // eslint-disable-next-line
                `<table class="table"><thead><tr class="row"></tr></thead><tbody><tr class="row"></tr></tbody></table>`
            );

            setTimeout(() => {
                expect(editor.getHtml()).toEqual('');
            });
        });

        it('remove table', () => {
            const cell = tableComponent.find('tr:nth-child(1) > td:nth-child(1)')[0];

            tableModify.modify('delete-table', {
                selected: cell
            });

            setTimeout(() => {
                expect(editor.getHtml()).toEqual('');
            });
        });
    });

    describe('check empty table', () => {
        let tableComponent;
        let tableModify;
        beforeEach(done => {
            editor.setComponents('<table></table>');

            tableComponent = editor.Components.getComponents().models[0];
            tableComponent.find('tr').forEach(tr => tr.remove());
            tableModify = new TableModify(tableComponent);
            setTimeout(() => done(), 0);
        });

        afterEach(done => {
            editor.setComponents([]);
            setTimeout(() => done(), 0);
        });

        it('add rows', () => {
            expect(tableComponent.find('tr').length).toEqual(0);
            expect(tableComponent.find('td').length).toEqual(0);
            tableModify.modify('insert-row-after', {
                selected: tableComponent
            });

            expect(tableComponent.find('tr').length).toEqual(1);
            expect(tableComponent.find('td').length).toEqual(1);
            tableModify.modify('insert-row-before', {
                selected: tableComponent
            });

            expect(tableComponent.find('tr').length).toEqual(2);
            expect(tableComponent.find('td').length).toEqual(2);

            expect(tableComponent.toHTML()).toEqual(
                // eslint-disable-next-line
                `<table class="table"><thead></thead><tbody><tr><td><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td></tr><tr><td><div>oro.cms.wysiwyg.component.table.body_cell_label</div></td></tr></tbody></table>`
            );
        });
    });
});
