import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkBlockBuilder from 'orocms/js/app/grapesjs/types/link-block-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/link-block-type', () => {
    let linkBlockBuilder;
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

    describe('component "LinkTypeBuilder"', () => {
        beforeEach(() => {
            linkBlockBuilder = new LinkBlockBuilder({
                editor,
                componentType: 'link-block'
            });

            linkBlockBuilder.execute();
        });

        afterEach(() => {
            linkBlockBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(linkBlockBuilder).toBeDefined();
            expect(linkBlockBuilder.componentType).toEqual('link-block');
        });

        it('check is component type defined', () => {
            const type = linkBlockBuilder.editor.DomComponents.getType('link-block');
            expect(type).toBeDefined();
            expect(type.id).toEqual('link-block');
        });

        it('check is component type button', () => {
            const button = linkBlockBuilder.editor.BlockManager.get(linkBlockBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');

            expect(button.get('content').style).toEqual({
                'display': 'inline-block',
                'padding': '5px',
                'min-height': '50px',
                'min-width': '50px'
            });
        });

        it('check component parent type', () => {
            expect(linkBlockBuilder.parentType).toEqual('link');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');
            mockElement.classList.add('link-block');

            expect(linkBlockBuilder.Model.isComponent).toBeDefined();
            expect(linkBlockBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(linkBlockBuilder.Model.componentType).toEqual(linkBlockBuilder.componentType);

            expect(linkBlockBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(linkBlockBuilder.Model.prototype.defaults.classes).toEqual(
                ['link-block']
            );
            expect(linkBlockBuilder.Model.prototype.defaults.components).toEqual([]);
            expect(linkBlockBuilder.Model.prototype.defaults.editable).toBe(false);
            expect(linkBlockBuilder.Model.prototype.defaults.droppable).toBe(true);

            expect(linkBlockBuilder.Model.prototype.editor).toBeDefined();
            expect(linkBlockBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let linkComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'link-block',
                    attributes: {
                        id: 'test'
                    }
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(linkComponent.toHTML()).toEqual('<a id="test" class="link-block"></a>');
            });

            it('check "toHTML" after update attributes', () => {
                linkComponent.addAttributes({
                    href: 'http://test.link',
                    title: 'Link title',
                    target: '_blank'
                }, {
                    silent: true
                });

                expect(linkComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<a id="test" href="http://test.link" title="Link title" target="_blank" class="link-block"></a>'
                );
            });

            it('check "getAttributes"', () => {
                expect(linkComponent.getAttrToHTML()).toEqual({
                    'class': 'link-block',
                    'id': 'test'
                });
            });
        });
    });
});
