import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkButtonTypeBuilder from 'orocms/js/app/grapesjs/types/link-button-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/link-block', () => {
    let linkButtonTypeBuilder;
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

    describe('component "LinkButtonTypeBuilder"', () => {
        beforeEach(() => {
            linkButtonTypeBuilder = new LinkButtonTypeBuilder({
                editor,
                componentType: 'link-button'
            });

            linkButtonTypeBuilder.execute();
        });

        afterEach(() => {
            linkButtonTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(linkButtonTypeBuilder).toBeDefined();
            expect(linkButtonTypeBuilder.componentType).toEqual('link-button');
        });

        it('check is component type defined', () => {
            const type = linkButtonTypeBuilder.editor.DomComponents.getType('link-button');
            expect(type).toBeDefined();
            expect(type.id).toEqual('link-button');
        });

        it('check is component type button', () => {
            const button = linkButtonTypeBuilder.editor.BlockManager.get(linkButtonTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check component parent type', () => {
            expect(linkButtonTypeBuilder.parentType).toEqual('link');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');
            mockElement.classList.add('btn');

            expect(linkButtonTypeBuilder.Model.isComponent).toBeDefined();
            expect(linkButtonTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(linkButtonTypeBuilder.Model.componentType).toEqual(linkButtonTypeBuilder.componentType);

            expect(linkButtonTypeBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(linkButtonTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['btn', 'btn--info']
            );

            expect(linkButtonTypeBuilder.Model.prototype.editor).toBeDefined();
            expect(linkButtonTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let linkBlockComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'link-button',
                    attributes: {
                        id: 'test'
                    }
                }]);

                linkBlockComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(linkBlockComponent.toHTML()).toEqual(
                    '<a id="test" class="btn btn--info">oro.cms.wysiwyg.component.link_button.content</a>'
                );
            });

            it('check "toHTML" after update attributes', () => {
                linkBlockComponent.addAttributes({
                    href: 'http://test.link',
                    title: 'Link title',
                    target: '_blank'
                }, {
                    silent: true
                });

                expect(linkBlockComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<a id="test" href="http://test.link" title="Link title" target="_blank" class="btn btn--info">oro.cms.wysiwyg.component.link_button.content</a>'
                );
            });

            it('check "getAttributes"', () => {
                expect(linkBlockComponent.getAttrToHTML()).toEqual({
                    'class': 'btn btn--info',
                    'id': 'test'
                });
            });
        });
    });
});
