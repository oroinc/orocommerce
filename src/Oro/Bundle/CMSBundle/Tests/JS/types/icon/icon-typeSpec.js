import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../../fixtures/grapesjs-editor-view-fixture.html';
import IconType from 'orocms/js/app/grapesjs/plugins/wysiwyg-icons/icon-type';
import 'orocms/js/app/grapesjs/plugins/wysiwyg-icons';

describe('orocms/js/app/grapesjs/types/icon', () => {
    let iconType;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            deviceManager: {
                devices: []
            },
            plugins: ['wysiwyg-icons'],
            pluginsOpts: {
                'wysiwyg-icons': {
                    baseSvgSpriteUrl: '/build/__theme__/svg-icons/theme-icons.svg'
                }
            }
        });

        editor.em.set('currentTheme', {
            name: 'test'
        });

        editor.ComponentRestriction = new ComponentRestriction(editor, {});

        editor.on('load', () => done());
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('component "IconType"', () => {
        beforeEach(() => {
            iconType = new IconType({
                editor,
                componentType: 'icon'
            });

            iconType.execute();
        });

        afterEach(() => {
            iconType.dispose();
        });

        it('check to be defined', () => {
            expect(iconType).toBeDefined();
            expect(iconType.componentType).toEqual('icon');
        });

        it('check is component type defined', () => {
            const type = iconType.editor.DomComponents.getType('icon');
            expect(type).toBeDefined();
            expect(type.id).toEqual('icon');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('SPAN');
            mockElement.setAttribute('data-init-icon', 'test-icon-id');

            expect(iconType.Model.isComponent).toBeDefined();
            expect(iconType.Model.isComponent(mockElement)).toEqual({
                type: 'icon',
                tagName: 'svg',
                iconId: 'test-icon-id'
            });
            expect(iconType.Model.componentType).toEqual(iconType.componentType);

            expect(iconType.Model.prototype.defaults.tagName).toEqual('div');
            expect(iconType.Model.prototype.defaults.iconId).toEqual('add-note');
        });

        describe('test type in editor scope', () => {
            let iconComponent;
            beforeEach(done => {
                editor.addComponents([{
                    type: 'icon',
                    tagName: 'svg',
                    iconId: 'add-note'
                }]);

                iconComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML"', () => {
                expect(iconComponent.toHTML()).toEqual('{{ widget_icon("add-note") }}');
            });

            it('check `toHTML` with attributes', () => {
                iconComponent.addAttributes({
                    'id': 'test-id',
                    'data-custom-attr': 'custom-value'
                });
                iconComponent.addClass('extra-class');
                expect(iconComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '{{ widget_icon("add-note", {"id":"test-id","data-custom-attr":"custom-value","class":"extra-class"}) }}'
                );
            });

            it('check change icon id', () => {
                iconComponent.set('iconId', 'other-icon');

                expect(iconComponent.toHTML()).toEqual('{{ widget_icon("other-icon") }}');
            });
        });
    });
});
