import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import RteEditorPlugin from 'orocms/js/app/grapesjs/plugins/oro-rte-editor';
import ContentWidgetTypeBuilder from 'orocms/js/app/grapesjs/types/content-widget-type';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/content-widget-type', () => {
    let editor;
    let contentWidgetTypeBuilder;
    let mockElement;

    beforeEach(done => {
        mockElement = document.createElement('DIV');
        mockElement.classList.add('content-widget');

        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            plugins: [RteEditorPlugin],
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

    describe('component "ContentWidgetTypeBuilder"', () => {
        beforeEach(() => {
            contentWidgetTypeBuilder = new ContentWidgetTypeBuilder({
                editor,
                componentType: 'content-widget'
            });

            contentWidgetTypeBuilder.execute();
        });

        afterEach(() => {
            contentWidgetTypeBuilder.dispose();
        });

        it('check content block defined', () => {
            expect(contentWidgetTypeBuilder).toBeDefined();
            expect(contentWidgetTypeBuilder.componentType).toEqual('content-widget');
        });

        it('check is component type defined', () => {
            const type = contentWidgetTypeBuilder.editor.DomComponents.getType('content-widget');
            expect(type).toBeDefined();
            expect(type.id).toEqual('content-widget');
        });

        it('check is component type button', () => {
            const button = contentWidgetTypeBuilder.editor.BlockManager.get(contentWidgetTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Basic');
        });

        it('check base model extend', () => {
            expect(contentWidgetTypeBuilder.Model.isComponent).toBeDefined();
            expect(contentWidgetTypeBuilder.Model.isComponent(mockElement)).toBe(true);
            expect(contentWidgetTypeBuilder.Model.componentType).toEqual(contentWidgetTypeBuilder.componentType);

            expect(contentWidgetTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(contentWidgetTypeBuilder.Model.prototype.defaults.classes).toEqual(
                ['content-widget', 'content-placeholder']
            );
            expect(contentWidgetTypeBuilder.Model.prototype.defaults.contentWidget).toBeNull();
            expect(contentWidgetTypeBuilder.Model.prototype.defaults.droppable).toBe(false);
            expect(contentWidgetTypeBuilder.Model.prototype.defaults.editable).toBe(false);
            expect(contentWidgetTypeBuilder.Model.prototype.defaults.stylable).toBe(false);

            expect(contentWidgetTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor commands defined', () => {
            expect(contentWidgetTypeBuilder.editor.Commands.get('content-widget-settings')).toBeDefined();
            expect(contentWidgetTypeBuilder.editor.Commands.get('inline-content-widget-settings')).toBeDefined();
        });

        it('check inline editor action added', () => {
            const action = contentWidgetTypeBuilder
                .editor
                .RteEditor
                .collection
                .find(action => action.get('name') === 'inlineWidget');

            expect(action).toBeDefined();
            expect(typeof action.get('result')).toEqual('function');
            expect(action.get('order')).toEqual(50);
        });

        it('check editor events defined', () => {
            expect(contentWidgetTypeBuilder.editor.editor._events['component:deselected']).toBeDefined();
            expect(contentWidgetTypeBuilder.editor.editor._events['component:selected']).toBeDefined();
        });

        describe('test type in editor scope', () => {
            let contentWidgetComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'content-widget',
                    components: [
                        {
                            type: 'textnode',
                            content: '{{ widget("content-widget-name-default") }}'
                        }
                    ],
                    attributes: {
                        'class': ['content-widget'],
                        'id': 'test'
                    }
                }]);

                contentWidgetComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(contentWidgetComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<div class="content-widget content-placeholder" id="test">{{ widget(&quot;content-widget-name-default&quot;) }}</div>'
                );
            });

            it('check "toHTML" after content block change', () => {
                contentWidgetComponent.set('contentWidget', {
                    get(name) {
                        return this[name];
                    },
                    name: 'content-widget-name',
                    title: 'Content widget title'
                });

                expect(contentWidgetComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<div data-title="content-widget-name" id="test" class="content-widget content-placeholder">{{ widget(&quot;content-widget-name&quot;) }}</div>'
                );
            });
        });
    });
});
