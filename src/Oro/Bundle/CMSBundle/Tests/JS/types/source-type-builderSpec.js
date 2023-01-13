import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import SourceTypeBuilder from 'orocms/js/app/grapesjs/types/source-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/source-type', () => {
    let sourceTypeBuilder;
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

    describe('component "SourceTypeBuilder"', () => {
        beforeEach(() => {
            sourceTypeBuilder = new SourceTypeBuilder({
                editor,
                componentType: 'source'
            });

            sourceTypeBuilder.execute();
        });

        afterEach(() => {
            sourceTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(sourceTypeBuilder).toBeDefined();
            expect(sourceTypeBuilder.componentType).toEqual('source');
        });

        it('check is component type defined', () => {
            const type = sourceTypeBuilder.editor.DomComponents.getType('source');
            expect(type).toBeDefined();
            expect(type.id).toEqual('source');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('SOURCE');
            expect(sourceTypeBuilder.Model.isComponent).toBeDefined();
            expect(sourceTypeBuilder.Model.isComponent(mockElement)).toBe(true);

            expect(sourceTypeBuilder.Model.componentType).toEqual(sourceTypeBuilder.componentType);
            expect(sourceTypeBuilder.Model.prototype.defaults.tagName).toEqual('source');
            expect(sourceTypeBuilder.Model.prototype.defaults.attributes).toEqual({
                srcset: '',
                type: '',
                media: '',
                sizes: ''
            });

            expect(sourceTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let sourceComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'source'
                }]);

                sourceComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(sourceComponent.toHTML()).toEqual('<source/>');
            });

            it('check "toHTML" after update attributes', () => {
                sourceComponent.addAttributes({
                    srcset: 'http://testsrcset.loc',
                    type: 'type/test',
                    media: 'media test',
                    sizes: 'test sizes'
                });

                expect(sourceComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<source srcset="http://testsrcset.loc" type="type/test" media="media test" sizes="test sizes"/>'
                );
            });
        });
    });
});
