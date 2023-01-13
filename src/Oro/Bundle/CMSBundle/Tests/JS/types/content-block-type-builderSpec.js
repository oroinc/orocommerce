import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import ContentBlockTypeBuilder from 'orocms/js/app/grapesjs/types/content-block-type';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/content-block-type', () => {
    let editor;
    let contentBlockTypeBuilder;
    let mockElement;

    beforeEach(done => {
        mockElement = document.createElement('DIV');
        mockElement.classList.add('content-block');

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

    describe('component "ContentBlockTypeBuilder"', () => {
        beforeEach(() => {
            contentBlockTypeBuilder = new ContentBlockTypeBuilder({
                editor,
                componentType: 'content-block'
            });

            contentBlockTypeBuilder.execute();
        });

        it('check content block defined', () => {
            expect(contentBlockTypeBuilder).toBeDefined();
            expect(contentBlockTypeBuilder.componentType).toEqual('content-block');
        });

        it('check is component type defined', () => {
            const type = contentBlockTypeBuilder.editor.DomComponents.getType('content-block');
            expect(type).toBeDefined();
            expect(type.id).toEqual('content-block');
        });

        it('check is component type button', () => {
            const button = contentBlockTypeBuilder.editor.BlockManager.get(contentBlockTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check base model extend', () => {
            expect(contentBlockTypeBuilder.Model.isComponent).toBeDefined();
            expect(contentBlockTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(contentBlockTypeBuilder.Model.componentType).toEqual(contentBlockTypeBuilder.componentType);

            expect(contentBlockTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(contentBlockTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['content-block', 'content-placeholder']
            );
            expect(contentBlockTypeBuilder.Model.prototype.defaults.contentBlock).toBeNull();
            expect(contentBlockTypeBuilder.Model.prototype.defaults.droppable).toBe(false);

            expect(contentBlockTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor commands defined', () => {
            expect(contentBlockTypeBuilder.editor.Commands.get('content-block-settings')).toBeDefined();
        });

        describe('test type in editor scope', () => {
            let contentBlockComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'content-block',
                    components: [
                        {
                            type: 'textnode',
                            content: '{{ content_block("content-block-alias-default") }}'
                        }
                    ],
                    attributes: {
                        'class': ['content-block'],
                        'id': 'test'
                    }
                }]);

                contentBlockComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(contentBlockComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<div class="content-block content-placeholder" id="test">{{ content_block(&quot;content-block-alias-default&quot;) }}</div>'
                );
            });

            it('check "toHTML" after content block change', () => {
                contentBlockComponent.set('contentBlock', {
                    get(name) {
                        return this[name];
                    },
                    alias: 'content-block-alias',
                    title: 'Content block title'
                });

                expect(contentBlockComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<div data-title="Content block title" id="test" class="content-block content-placeholder">{{ content_block(&quot;content-block-alias&quot;) }}</div>'
                );
            });
        });
    });
});
