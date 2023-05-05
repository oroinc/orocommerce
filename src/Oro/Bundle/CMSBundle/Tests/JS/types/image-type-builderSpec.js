import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ImageTypeBuilder from 'orocms/js/app/grapesjs/types/image';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/image', () => {
    let imageTypeBuilder;
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
            expect(imageTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(imageTypeBuilder.Model.componentType).toEqual(imageTypeBuilder.componentType);

            expect(imageTypeBuilder.Model.prototype.defaults.tagName).toEqual('img');
            expect(imageTypeBuilder.Model.prototype.defaults.previewMetadata).toEqual({});

            expect(imageTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check commands', () => {
            expect(imageTypeBuilder.commands['open-digital-assets']).toEqual(openDigitalAssetsCommand);
        });

        describe('test type in editor scope', () => {
            let imageComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'image',
                    attributes: {
                        id: 'test'
                    }
                }]);

                imageComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(imageComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<img id="test" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3R5bGU9ImZpbGw6IHJnYmEoMCwwLDAsMC4xNSk7IHRyYW5zZm9ybTogc2NhbGUoMC43NSkiPgogICAgICAgIDxwYXRoIGQ9Ik04LjUgMTMuNWwyLjUgMyAzLjUtNC41IDQuNSA2SDVtMTYgMVY1YTIgMiAwIDAgMC0yLTJINWMtMS4xIDAtMiAuOS0yIDJ2MTRjMCAxLjEuOSAyIDIgMmgxNGMxLjEgMCAyLS45IDItMnoiPjwvcGF0aD4KICAgICAgPC9zdmc+"/>'
                );
            });

            it('check "toHTML" after update attributes', () => {
                imageComponent.set('src', 'http://testlink.loc').addAttributes({
                    alt: 'Image title'
                });

                expect(imageComponent.toHTML()).toEqual(
                    '<img id="test" src="http://testlink.loc" alt="Image title"/>'
                );
            });
        });
    });
});
