import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import MapTypeBuilder from 'orocms/js/app/grapesjs/types/map-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/grid-type', () => {
    let mapTypeBuilder;
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

    describe('component "MapTypeBuilder"', () => {
        beforeEach(() => {
            mapTypeBuilder = new MapTypeBuilder({
                editor,
                componentType: 'map'
            });

            mapTypeBuilder.execute();
        });

        afterEach(() => {
            mapTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(mapTypeBuilder.componentType).toEqual('map');
        });

        it('check is component type defined', () => {
            const type = mapTypeBuilder.editor.DomComponents.getType('map');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'map'
            }));
        });

        it('check is component type button', () => {
            const button = mapTypeBuilder.editor.BlockManager.get(mapTypeBuilder.componentType);
            expect(button.get('content').style).toEqual({
                height: '350px',
                width: '100%'
            });
        });

        it('check component parent type', () => {
            expect(mapTypeBuilder.parentType).toEqual('map');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('IFRAME');
            mockElement.setAttribute('src', 'http://maps.google.com/');

            expect(mapTypeBuilder.Model.isComponent).toBeDefined();
            expect(mapTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: mapTypeBuilder.componentType,
                src: 'http://maps.google.com/'
            });
            expect(mapTypeBuilder.Model.componentType).toEqual(mapTypeBuilder.componentType);

            expect(mapTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let mapComponent;
            beforeEach(done => {
                editor.Components.getComponents().add([{
                    type: 'map'
                }], {
                    silent: true
                });

                mapComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(mapComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<iframe frameborder="0" src="https://maps.google.com/maps?&z=1&t=q&output=embed"></iframe>'
                );
            });
        });
    });
});
