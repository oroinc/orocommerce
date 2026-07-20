import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import _ from 'underscore';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/types/base-type';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/base-type', () => {
    let baseTypeBuilder;
    let editor;

    const TestTypeBuilder = BaseTypeBuilder.extend({
        modelProps: {
            defaults: {
                testValue1: 'Test Value 1',
                testValue2: 'Test Value 2',
                testValue3: 'Test Value 3'
            }
        },

        button: {
            label: 'Test',
            category: 'Test',
            attributes: {
                'class': 'fa fa-code'
            }
        },

        template: _.template(`<div>Test</div>`),

        commands: {
            'test-command1': () => {
                return true;
            },
            'test-command2': () => {
                return true;
            }
        },

        isComponent() {
            return {
                type: 'test'
            };
        }
    }, {
        type: 'test'
    });

    const ToolbarTestTypeBuilder = BaseTypeBuilder.extend({
        modelProps: {
            defaults: {
                tagName: 'div',
                mainToolbarAction: 'test-action'
            },

            init() {
                this.mergeToolbarItems([
                    {
                        id: 'test-action',
                        attributes: {
                            'class': 'fa fa-gear',
                            'label': 'Test'
                        },
                        command: 'test-toolbar-command'
                    }
                ]);
            }
        },

        viewProps: {
            events: {
                dblclick: 'onDoubleClick'
            }
        },

        button: {
            label: 'Toolbar Test',
            category: 'Test',
            attributes: {
                'class': 'fa fa-code'
            }
        },

        template: _.template(`<div>Toolbar Test</div>`),

        commands: {
            'test-toolbar-command': {
                run(editor) {
                    editor.testCommandExecuted = true;
                }
            }
        },

        isComponent(el) {
            if (el.nodeType === Node.ELEMENT_NODE && el.getAttribute('data-type') === 'toolbar-test') {
                return {type: 'toolbar-test'};
            }
        }
    }, {
        type: 'toolbar-test'
    });

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

    describe('component "BaseTypeBuilder"', () => {
        beforeEach(() => {
            baseTypeBuilder = new TestTypeBuilder({
                editor,
                componentType: 'test'
            });

            baseTypeBuilder.execute();
        });

        afterEach(() => {
            baseTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(baseTypeBuilder).toBeDefined();
            expect(baseTypeBuilder).toBeInstanceOf(TestTypeBuilder);
            expect(baseTypeBuilder.componentType).toEqual('test');
        });

        it('check is component type defined', () => {
            const type = baseTypeBuilder.editor.DomComponents.getType('test');
            expect(type).toBeDefined();
            expect(type.id).toEqual('test');
        });

        it('check is component type button', () => {
            const button = baseTypeBuilder.editor.BlockManager.get(baseTypeBuilder.componentType);
            expect(button).toBeDefined();
            expect(button.get('category').get('label')).toEqual('Test');
        });

        it('check base model extend', () => {
            expect(baseTypeBuilder.Model.isComponent).toBeDefined();
            expect(baseTypeBuilder.Model.isComponent()).toEqual({
                type: 'test'
            });
            expect(baseTypeBuilder.Model.componentType).toEqual(TestTypeBuilder.type);

            expect(baseTypeBuilder.Model.prototype.defaults.testValue1).toEqual('Test Value 1');
            expect(baseTypeBuilder.Model.prototype.defaults.testValue2).toEqual('Test Value 2');
            expect(baseTypeBuilder.Model.prototype.defaults.testValue3).toEqual('Test Value 3');

            expect(baseTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        it('check editor commands defined', () => {
            expect(baseTypeBuilder.editor.Commands.get('test-command1')).toBeDefined();
            expect(baseTypeBuilder.editor.Commands.get('test-command2')).toBeDefined();
        });
    });

    describe('baseModelMethods', () => {
        let toolbarTestBuilder;

        beforeEach(() => {
            toolbarTestBuilder = new ToolbarTestTypeBuilder({
                editor,
                componentType: 'toolbar-test'
            });

            toolbarTestBuilder.execute();
        });

        afterEach(() => {
            toolbarTestBuilder.dispose();
        });

        describe('mergeToolbarItems', () => {
            let component;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'toolbar-test',
                    attributes: {id: 'merge-test'}
                }]);

                component = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should add toolbar items during init', () => {
                const toolbar = component.get('toolbar');
                const testAction = toolbar.find(item => item.id === 'test-action');

                expect(testAction).toBeDefined();
                expect(testAction.command).toEqual('test-toolbar-command');
            });

            it('should not duplicate items on repeated merge', () => {
                component.mergeToolbarItems([
                    {
                        id: 'test-action',
                        attributes: {
                            'class': 'fa fa-gear',
                            'label': 'Duplicate'
                        },
                        command: 'test-toolbar-command'
                    }
                ]);

                const toolbar = component.get('toolbar');
                const matches = toolbar.filter(item => item.id === 'test-action');

                expect(matches.length).toEqual(1);
            });

            it('should prepend new items to the toolbar', () => {
                component.mergeToolbarItems([
                    {
                        id: 'another-action',
                        attributes: {
                            'class': 'fa fa-plus',
                            'label': 'Another'
                        },
                        command: 'another-command'
                    }
                ]);

                const toolbar = component.get('toolbar');

                expect(toolbar[0].id).toEqual('another-action');
            });

            it('should handle empty items gracefully', () => {
                const toolbarBefore = component.get('toolbar').length;

                component.mergeToolbarItems([]);
                component.mergeToolbarItems(null);
                component.mergeToolbarItems(undefined);

                expect(component.get('toolbar').length).toEqual(toolbarBefore);
            });

            it('should deduplicate by command when id is not set', () => {
                component.set('toolbar', [
                    {command: 'some-command', attributes: {'class': 'fa fa-star'}}
                ]);

                component.mergeToolbarItems([
                    {command: 'some-command', attributes: {'class': 'fa fa-heart'}}
                ]);

                const toolbar = component.get('toolbar');
                const matches = toolbar.filter(item => item.command === 'some-command');

                expect(matches.length).toEqual(1);
            });
        });
    });

    describe('baseViewMethods', () => {
        let toolbarTestBuilder;

        beforeEach(() => {
            toolbarTestBuilder = new ToolbarTestTypeBuilder({
                editor,
                componentType: 'toolbar-test'
            });

            toolbarTestBuilder.execute();
        });

        afterEach(() => {
            toolbarTestBuilder.dispose();
        });

        describe('onDoubleClick', () => {
            let component;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'toolbar-test',
                    attributes: {id: 'dblclick-test'}
                }]);

                component = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should execute the command of the mainToolbarAction item', () => {
                editor.testCommandExecuted = false;

                const view = component.getView();
                const event = new Event('dblclick', {bubbles: true});

                view.el.dispatchEvent(event);

                expect(editor.testCommandExecuted).toBe(true);
            });

            it('should stop event propagation', () => {
                const view = component.getView();
                const event = new Event('dblclick', {bubbles: true});

                spyOn(event, 'stopPropagation');
                view.onDoubleClick(event);

                expect(event.stopPropagation).toHaveBeenCalled();
            });

            it('should do nothing when mainToolbarAction is not set', () => {
                component.set('mainToolbarAction', null, {silent: true});
                editor.testCommandExecuted = false;

                const view = component.getView();
                const event = new Event('dblclick', {bubbles: true});

                view.onDoubleClick(event);

                expect(editor.testCommandExecuted).toBe(false);
            });

            it('should do nothing when toolbar item is not found', () => {
                component.set('mainToolbarAction', 'non-existent-action', {silent: true});
                editor.testCommandExecuted = false;

                const view = component.getView();
                const event = new Event('dblclick', {bubbles: true});

                view.onDoubleClick(event);

                expect(editor.testCommandExecuted).toBe(false);
            });

            it('should execute function commands directly', () => {
                let functionCalled = false;

                component.set('toolbar', [
                    {
                        id: 'fn-action',
                        command: () => {
                            functionCalled = true;
                        }
                    }
                ]);
                component.set('mainToolbarAction', 'fn-action', {silent: true});

                const view = component.getView();
                const event = new Event('dblclick', {bubbles: true});

                view.onDoubleClick(event);

                expect(functionCalled).toBe(true);
            });
        });
    });
});
