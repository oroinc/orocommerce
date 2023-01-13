import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import TextTypeBuilder from 'orocms/js/app/grapesjs/types/text';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import ContentParser from 'orocms/js/app/grapesjs/plugins/grapesjs-content-parser';

describe('orocms/js/app/grapesjs/types/text-type', () => {
    let textTypeBuilder;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            plugins: [ContentParser],
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

    describe('component "TextTypeBuilder"', () => {
        beforeEach(() => {
            textTypeBuilder = new TextTypeBuilder({
                editor,
                componentType: 'text'
            });

            textTypeBuilder.execute();
        });

        afterEach(() => {
            textTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(textTypeBuilder).toBeDefined();
            expect(textTypeBuilder.componentType).toEqual('text');
        });

        it('check is component type defined', () => {
            const type = textTypeBuilder.editor.DomComponents.getType('text');
            expect(type).toBeDefined();
            expect(type.id).toEqual('text');
        });

        it('check component parent type', () => {
            expect(textTypeBuilder.parentType).toEqual('text');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('P');
            mockElement.innerText = 'Text content';

            expect(textTypeBuilder.Model.isComponent).toBeDefined();
            expect(textTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: textTypeBuilder.componentType,
                tagName: 'p'
            });

            expect(textTypeBuilder.Model.componentType).toEqual(textTypeBuilder.componentType);

            expect(textTypeBuilder.Model.prototype.defaults.tagName).toEqual('div');
            expect(textTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let textComponent;
            let textNode;
            beforeEach(() => {
                editor.setComponents('<div>Test content</div>');

                textComponent = editor.Components.getComponents().models[0];
                textNode = textComponent.findType('textnode')[0];
            });

            afterEach(() => {
                editor.setComponents('');
            });

            it('is defined', () => {
                expect(textComponent).toBeDefined();
                expect(textComponent.get('tagName')).toEqual('div');
                expect(textNode.get('content')).toEqual('Test content');
            });

            it('is single line', () => {
                expect(textComponent.view.isSingleLine()).toBe(false);
            });

            it('set simple content', () => {
                const content = '<p>New test content</p>';
                textComponent.view.getContent = () => content;
                textComponent.setContent(content);

                expect(textComponent.components().length).toEqual(1);
                expect(textComponent.findType('text')[0].get('tagName')).toEqual('p');
                expect(textComponent.findType('textnode')[0].get('content')).toEqual('New test content');
                expect(textComponent.toHTML()).toEqual(`<div>${content}</div>`);
            });

            it('set complex content', () => {
                // eslint-disable-next-line
                const content = '<p>New test content</p><ul><li>Item 1</li><li>Item 2</li></ul><p>Lorem <b>ipsum</b></p>';
                textComponent.view.getContent = () => content;
                textComponent.setContent(content);

                expect(textComponent.components().length).toEqual(3);
                expect(textComponent.findType('text').length).toEqual(6);
                expect(textComponent.findType('textnode').length).toEqual(5);
                expect(textComponent.findType('text')[0].get('tagName')).toEqual('p');
                expect(textComponent.findType('text')[1].get('tagName')).toEqual('ul');
                expect(textComponent.findType('text')[2].get('tagName')).toEqual('li');
                expect(textComponent.findType('text')[4].get('components').toJSON()[0].type).toEqual('textnode');
                expect(textComponent.findType('text')[4].get('components').toJSON()[0].content).toEqual('Lorem ');
                expect(textComponent.findType('text')[4].get('components').toJSON()[1].tagName).toEqual('b');
                expect(textComponent.findType('text')[4].get('components').toJSON()[1].components.length).toEqual(1);
                expect(
                    textComponent.findType('text')[4].get('components').toJSON()[1].components.toJSON()[0].content
                ).toEqual('ipsum');
                expect(textComponent.findType('textnode')[0].get('content')).toEqual('New test content');
                expect(textComponent.toHTML()).toEqual(`<div>${content}</div>`);
            });
        });
    });
});
