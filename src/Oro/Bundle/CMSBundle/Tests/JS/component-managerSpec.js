import grapesJS from 'grapesjs';
import 'jasmine-jquery';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import DefaultTypeBuilder from 'orocms/js/app/grapesjs/type-builders/default-type-builder';
import TextTypeBuilder from 'orocms/js/app/grapesjs/type-builders/text-type-builder';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/plugins/components/component-manager', () => {
    let editor;
    let componentManager;

    beforeEach(() => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor')
        });
        editor.ComponentRestriction = new ComponentRestriction(editor, {});
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('feature "ComponentManager"', () => {
        beforeEach(() => {
            componentManager = new ComponentManager({
                editor
            });
        });

        afterEach(() => {
            ComponentManager.componentTypes = {};
            componentManager.dispose();
        });

        it('check module be defined', () => {
            expect(componentManager).toBeDefined();
            expect(componentManager).toBeInstanceOf(ComponentManager);
        });

        it('check static properties and methods', () => {
            expect(ComponentManager.componentTypes).toEqual({});
            expect(ComponentManager.registerComponentType).toEqual(jasmine.any(Function));
            expect(ComponentManager.registerComponentTypes).toEqual(jasmine.any(Function));
        });

        it('register single component type', () => {
            ComponentManager.registerComponentType('default', DefaultTypeBuilder);

            expect(Object.values(ComponentManager.componentTypes).length).toEqual(1);
            expect(ComponentManager.componentTypes.default).toBeDefined();
            expect(ComponentManager.componentTypes.default).toEqual(jasmine.any(Function));
        });

        it('register multiple component types', () => {
            ComponentManager.registerComponentTypes({
                'default': DefaultTypeBuilder,
                'text': TextTypeBuilder
            });

            expect(Object.values(ComponentManager.componentTypes).length).toEqual(2);
            expect(ComponentManager.componentTypes.default).toBe(DefaultTypeBuilder);
            expect(ComponentManager.componentTypes.default).toEqual(jasmine.any(Function));
            expect(ComponentManager.componentTypes.text).toBe(TextTypeBuilder);
            expect(ComponentManager.componentTypes.text).toEqual(jasmine.any(Function));
        });

        it('apply component types', () => {
            ComponentManager.registerComponentTypes({
                'default': {
                    Constructor: DefaultTypeBuilder
                },
                'text': {
                    Constructor: TextTypeBuilder
                }
            });

            componentManager.applyTypeBuilders();

            expect(componentManager.typeBuilders.length).toEqual(2);
            expect(componentManager.typeBuilders[0].componentType).toEqual('default');
            expect(componentManager.typeBuilders[1].componentType).toEqual('text');
        });

        it('dispose component manager', () => {
            ComponentManager.registerComponentTypes({
                'default': {
                    Constructor: DefaultTypeBuilder
                },
                'text': {
                    Constructor: TextTypeBuilder
                }
            });

            componentManager.applyTypeBuilders();
            componentManager.dispose();

            expect(componentManager.typeBuilders[0].disposed).toBeTruthy();
            expect(componentManager.typeBuilders[1].disposed).toBeTruthy();
        });
    });
});
