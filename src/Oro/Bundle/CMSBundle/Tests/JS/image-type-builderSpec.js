import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ImageTypeBuilder from 'orocms/js/app/grapesjs/type-builders/image-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/image-type-builder', () => {
    let imageTypeBuilder;
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

    describe('component "ImageTypeBuilder"', () => {
        beforeEach(() => {
            imageTypeBuilder = new ImageTypeBuilder({
                editor,
                componentType: 'image'
            });

            imageTypeBuilder.execute();
        });

        afterEach(() => {
            imageTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(imageTypeBuilder).toBeDefined();
            expect(imageTypeBuilder.componentType).toEqual('image');
        });

        it('check is component type defined', () => {
            const type = imageTypeBuilder.editor.DomComponents.getType('image');
            expect(type).toBeDefined();
            expect(type.id).toEqual('image');
        });

        it('check component parent type', () => {
            expect(imageTypeBuilder.parentType).toEqual('image');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('IMG');

            expect(imageTypeBuilder.Model.isComponent).toBeDefined();
            expect(imageTypeBuilder.Model.isComponent(mockElement)).toBeTruthy();
            expect(imageTypeBuilder.Model.componentType).toEqual(imageTypeBuilder.componentType);

            expect(imageTypeBuilder.Model.prototype.defaults.tagName).toEqual('img');
            expect(imageTypeBuilder.Model.prototype.defaults.previewMetadata).toEqual({});

            expect(imageTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check commands', () => {
            expect(imageTypeBuilder.commands['open-digital-assets']).toEqual(openDigitalAssetsCommand);
        });
    });
});
