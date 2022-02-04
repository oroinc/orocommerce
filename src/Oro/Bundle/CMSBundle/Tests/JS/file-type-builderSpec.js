import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import FileTypeBuilder from 'orocms/js/app/grapesjs/type-builders/file-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/image-type-builder', () => {
    let fileTypeBuilder;
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

    describe('component "FileTypeBuilder"', () => {
        beforeEach(() => {
            fileTypeBuilder = new FileTypeBuilder({
                editor,
                componentType: 'file'
            });

            fileTypeBuilder.execute();
        });

        afterEach(() => {
            fileTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(fileTypeBuilder).toBeDefined();
            expect(fileTypeBuilder.componentType).toEqual('file');
        });

        it('check is component type defined', () => {
            const type = fileTypeBuilder.editor.DomComponents.getType('file');
            expect(type).toBeDefined();
            expect(type.id).toEqual('file');
        });

        it('check component parent type', () => {
            expect(fileTypeBuilder.parentType).toEqual('link');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');
            mockElement.classList.add('digital-asset-file');

            expect(fileTypeBuilder.Model.isComponent).toBeDefined();
            expect(fileTypeBuilder.Model.isComponent(mockElement)).toBeTruthy();
            expect(fileTypeBuilder.Model.componentType).toEqual(fileTypeBuilder.componentType);

            expect(fileTypeBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(fileTypeBuilder.Model.prototype.defaults.type).toEqual('file');
            expect(fileTypeBuilder.Model.prototype.defaults.classes).toEqual(['digital-asset-file', 'no-hash']);
            expect(fileTypeBuilder.Model.prototype.defaults.activeOnRender).toEqual(1);
            expect(fileTypeBuilder.Model.prototype.defaults.void).toEqual(0);
            expect(fileTypeBuilder.Model.prototype.defaults.droppable).toEqual(1);
            expect(fileTypeBuilder.Model.prototype.defaults.editable).toEqual(1);
            expect(fileTypeBuilder.Model.prototype.defaults.highlightable).toEqual(0);
            expect(fileTypeBuilder.Model.prototype.defaults.resizable).toEqual(0);
            expect(fileTypeBuilder.Model.prototype.defaults.traits).toEqual(['title', 'text', 'target']);

            expect(fileTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check commands', () => {
            expect(fileTypeBuilder.commands['open-digital-assets']).toEqual(openDigitalAssetsCommand);
        });
    });
});
