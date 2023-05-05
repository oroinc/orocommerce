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
});
