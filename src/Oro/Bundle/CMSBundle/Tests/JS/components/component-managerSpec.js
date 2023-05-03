import grapesJS from 'grapesjs';
import 'jasmine-jquery';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import QuoteTypeBuilder from 'orocms/js/app/grapesjs/types/quote-type';
import TextTypeBuilder from 'orocms/js/app/grapesjs/types/text';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/plugins/components/component-manager', () => {
    let editor;
    let componentManager;
    let componentTypes = {};

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

    describe('feature "ComponentManager"', () => {
        beforeEach(() => {
            componentTypes = ComponentManager.componentTypes;
            ComponentManager.componentTypes = {};
            componentManager = new ComponentManager({
                editor
            });
        });

        afterEach(() => {
            componentManager.dispose();
            ComponentManager.componentTypes = componentTypes;
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
            ComponentManager.registerComponentType('quote', QuoteTypeBuilder);

            expect(Object.values(ComponentManager.componentTypes).length).toEqual(1);
            expect(ComponentManager.componentTypes.quote).toBeDefined();
            expect(ComponentManager.componentTypes.quote).toEqual(jasmine.any(Function));
        });

        it('register multiple component types', () => {
            ComponentManager.registerComponentTypes({
                quote: QuoteTypeBuilder,
                text: TextTypeBuilder
            });

            expect(Object.values(ComponentManager.componentTypes).length).toEqual(2);
            expect(ComponentManager.componentTypes.quote).toBe(QuoteTypeBuilder);
            expect(ComponentManager.componentTypes.quote).toEqual(jasmine.any(Function));
            expect(ComponentManager.componentTypes.text).toBe(TextTypeBuilder);
            expect(ComponentManager.componentTypes.text).toEqual(jasmine.any(Function));
        });

        it('apply component types', () => {
            ComponentManager.registerComponentTypes({
                quote: {
                    Constructor: QuoteTypeBuilder
                },
                text: {
                    Constructor: TextTypeBuilder
                }
            });

            componentManager.applyTypeBuilders();

            expect(componentManager.typeBuilders.length).toEqual(2);
            expect(componentManager.typeBuilders[0].componentType).toEqual('quote');
            expect(componentManager.typeBuilders[1].componentType).toEqual('text');
        });

        it('dispose component manager', () => {
            ComponentManager.registerComponentTypes({
                quote: {
                    Constructor: QuoteTypeBuilder
                },
                text: {
                    Constructor: TextTypeBuilder
                }
            });

            componentManager.applyTypeBuilders();
            componentManager.dispose();

            expect(componentManager.typeBuilders[0].disposed).toBe(true);
            expect(componentManager.typeBuilders[1].disposed).toBe(true);
        });
    });
});
