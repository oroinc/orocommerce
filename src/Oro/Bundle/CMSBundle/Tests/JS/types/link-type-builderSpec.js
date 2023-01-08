import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkTypeBuilder from 'orocms/js/app/grapesjs/types/link-type';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/link-type', () => {
    let linkTypeBuilder;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            deviceManager: {
                devices: []
            }
        });
        editor.BlockManager.add('link', {
            label: 'Test Link',
            content: '<a href="#">Test link</a>',
            category: 'Basic'
        });
        editor.ComponentRestriction = new ComponentRestriction(editor, {});

        editor.on('load', () => done());
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('component "LinkTypeBuilder"', () => {
        beforeEach(() => {
            linkTypeBuilder = new LinkTypeBuilder({
                editor,
                componentType: 'link'
            });

            linkTypeBuilder.execute();
        });

        afterEach(() => {
            linkTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(linkTypeBuilder).toBeDefined();
            expect(linkTypeBuilder.componentType).toEqual('link');
        });

        it('check is component type defined', () => {
            const type = linkTypeBuilder.editor.DomComponents.getType('link');
            expect(type).toBeDefined();
            expect(type.id).toEqual('link');
        });

        it('check is component type button', () => {
            const button = linkTypeBuilder.editor.BlockManager.get(linkTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category')).toEqual('Basic');
        });

        it('check editor commands defined', () => {
            expect(linkTypeBuilder.editor.Commands.get('open-create-link-dialog')).toBeDefined();
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('A');

            expect(linkTypeBuilder.Model.isComponent).toBeDefined();
            expect(linkTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(linkTypeBuilder.Model.componentType).toEqual(linkTypeBuilder.componentType);

            expect(linkTypeBuilder.Model.prototype.defaults.tagName).toEqual('a');
            expect(linkTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['link']
            );
            expect(linkTypeBuilder.Model.prototype.defaults.components.length).toEqual(1);
            expect(linkTypeBuilder.Model.prototype.defaults.editable).toBe(false);

            expect(linkTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let linkComponent;
            beforeEach(done => {
                editor.Components.getComponents().reset([{
                    type: 'link',
                    attributes: {
                        id: 'test'
                    }
                }], {
                    silent: true
                });

                linkComponent = editor.Components.getComponents().models[0];

                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(linkComponent.toHTML()).toEqual(
                    '<a id="test" class="link">oro.cms.wysiwyg.component.link.content</a>'
                );
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
                    '<a id="test" href="http://test.link" title="Link title" target="_blank" class="link">oro.cms.wysiwyg.component.link.content</a>'
                );
            });

            it('check "getAttributes"', () => {
                expect(linkComponent.getAttrToHTML()).toEqual({
                    'class': 'link',
                    'id': 'test'
                });
            });
        });
    });
});
