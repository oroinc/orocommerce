import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import LinkType from 'orocms/js/app/grapesjs/types/link';
import fileTraitInit from 'orocms/js/app/grapesjs/plugins/traits/file-trait';
import hrefTraitInit from 'orocms/js/app/grapesjs/plugins/traits/href-trait';
import radioSelectTraitInit from 'orocms/js/app/grapesjs/plugins/traits/radio-select-trait';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/link', () => {
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
        editor.ComponentRestriction = new ComponentRestriction(editor, {});

        fileTraitInit({editor});
        hrefTraitInit({editor});
        radioSelectTraitInit({editor});

        editor.on('load', () => done());
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('component "LinkType"', () => {
        beforeEach(() => {
            linkTypeBuilder = new LinkType({
                editor,
                componentType: 'link'
            });

            linkTypeBuilder.execute();
        });

        afterEach(() => {
            linkTypeBuilder.dispose();
        });

        it('should be defined', () => {
            expect(linkTypeBuilder).toBeDefined();
            expect(linkTypeBuilder.componentType).toEqual('link');
        });

        it('should register the link component type', () => {
            const type = editor.DomComponents.getType('link');

            expect(type).toBeDefined();
            expect(type.id).toEqual('link');
        });

        it('should create a link block in the panel', () => {
            const button = editor.BlockManager.get('link');

            expect(button).toBeDefined();
        });

        it('should register editor commands', () => {
            expect(editor.Commands.get('open-digital-assets')).toBeDefined();
        });

        it('should store LinkStyleRegistry on editor', () => {
            expect(editor.LinkStyleRegistry).toBeDefined();
            expect(editor.LinkStyleRegistry.has('link')).toBe(true);
            expect(editor.LinkStyleRegistry.has('button')).toBe(true);
        });

        describe('isComponent', () => {
            it('should identify anchor elements', () => {
                const el = document.createElement('A');

                expect(linkTypeBuilder.Model.isComponent(el)).toBeTruthy();
            });

            it('should not identify non-anchor elements', () => {
                const el = document.createElement('DIV');

                expect(linkTypeBuilder.Model.isComponent(el)).toBe(false);
            });

            it('should detect button style from element classes', () => {
                const el = document.createElement('A');

                el.classList.add('btn');

                const result = linkTypeBuilder.Model.isComponent(el);

                expect(result).toBeTruthy();
                expect(result.linkStyle).toEqual('button');
            });
        });

        describe('link style component', () => {
            let linkComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-link'}
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should have correct defaults', () => {
                expect(linkComponent.get('tagName')).toEqual('a');
                expect(linkComponent.get('linkStyle')).toEqual('link');
            });

            it('should not set a default mainToolbarAction', () => {
                expect(linkComponent.get('mainToolbarAction')).toBeUndefined();
            });

            it('should not have a link-settings toolbar item', () => {
                const toolbar = linkComponent.get('toolbar');
                const linkSettingsItem = toolbar.find(item => item.id === 'link-settings');

                expect(linkSettingsItem).toBeUndefined();
            });

            it('should render correct HTML', () => {
                expect(linkComponent.toHTML()).toContain('<a');
                expect(linkComponent.toHTML()).toContain('class="link"');
            });

            it('should sanitize attributes for HTML output', () => {
                const attrs = linkComponent.getAttrToHTML();

                expect(attrs.style).toBeUndefined();
                expect(attrs.onmousedown).toBeUndefined();
                expect(attrs['data-temp']).toBeUndefined();
                expect(attrs.text).toBeUndefined();
            });

            it('should not be droppable by default', () => {
                expect(linkComponent.get('droppable')).toBe(false);
            });

            it('should have containerMode trait', () => {
                expect(linkComponent.getTrait('containerMode')).toBeTruthy();
            });

            it('should enable droppable when containerMode is toggled on', () => {
                linkComponent.set('containerMode', true);

                expect(linkComponent.get('droppable')).toBe(true);
            });

            it('should remove text trait when containerMode is on', () => {
                linkComponent.set('containerMode', true);

                expect(linkComponent.getTrait('text')).toBeFalsy();
            });

            it('should restore text trait when containerMode is toggled off', () => {
                linkComponent.set('containerMode', true);
                linkComponent.set('containerMode', false);

                expect(linkComponent.getTrait('text')).toBeTruthy();
                expect(linkComponent.get('droppable')).toBe(false);
            });
        });

        describe('link container mode auto-detection', () => {
            let containerLink;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-container'},
                    components: [
                        {type: 'image', attributes: {src: 'test.png'}},
                        {type: 'textnode', content: 'Click here'}
                    ]
                }]);

                containerLink = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should auto-detect container mode from non-textnode children', () => {
                expect(containerLink.get('containerMode')).toBe(true);
                expect(containerLink.get('droppable')).toBe(true);
            });

            it('should not have text trait in container mode', () => {
                expect(containerLink.getTrait('text')).toBeFalsy();
            });
        });

        describe('button style component', () => {
            let buttonComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'button',
                    attributes: {id: 'test-button'}
                }]);

                buttonComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should have button style applied', () => {
                expect(buttonComponent.get('linkStyle')).toEqual('button');
                expect(buttonComponent.getClasses()).toContain('btn');
            });

            it('should render correct HTML', () => {
                expect(buttonComponent.toHTML()).toContain('class="btn"');
            });
        });

        describe('LinkStyleRegistry', () => {
            it('should allow registering custom styles', () => {
                editor.LinkStyleRegistry.register({
                    id: 'custom',
                    label: 'Custom',
                    order: 50,
                    classes: ['custom-link'],
                    detect(el) {
                        return el.classList.contains('custom-link');
                    }
                });

                expect(editor.LinkStyleRegistry.has('custom')).toBe(true);
                expect(editor.LinkStyleRegistry.getSelectOptions().length).toEqual(3);
            });

            it('should detect custom style from element', () => {
                editor.LinkStyleRegistry.register({
                    id: 'custom',
                    label: 'Custom',
                    classes: ['custom-link'],
                    detect(el) {
                        return el.classList.contains('custom-link');
                    }
                });

                const el = document.createElement('A');

                el.classList.add('custom-link');

                expect(editor.LinkStyleRegistry.detectFromElement(el)).toEqual('custom');
            });
        });

        describe('style switching', () => {
            let linkComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-switch'}
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should switch from link to button style', () => {
                linkComponent.set('linkStyle', 'button');

                expect(linkComponent.getClasses()).toContain('btn');
                expect(linkComponent.getClasses()).not.toContain('link');
            });

            it('should remove old style classes when switching styles', () => {
                linkComponent.set('linkStyle', 'button');

                expect(linkComponent.getClasses()).not.toContain('link');
                expect(linkComponent.getClasses()).toContain('btn');

                linkComponent.set('linkStyle', 'link');

                expect(linkComponent.getClasses()).not.toContain('btn');
                expect(linkComponent.getClasses()).toContain('link');
            });
        });

        describe('HTML output and attribute escaping', () => {
            let linkComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {
                        id: 'test-escape',
                        href: 'http://example.com?a=1&b=2',
                        title: 'Test <script>alert("xss")</script>'
                    }
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should not escape attribute values in getAttrToHTML (GrapesJS handles escaping)', () => {
                const attrs = linkComponent.getAttrToHTML();

                expect(attrs.title).toEqual('Test <script>alert("xss")</script>');
            });

            it('should not double-escape ampersands in toHTML output', () => {
                linkComponent.addAttributes({
                    href: 'http://example.com?a=1&b=2'
                });

                const html = linkComponent.toHTML();

                expect(html).toContain('&amp;');
                expect(html).not.toContain('&amp;amp;');
            });

            it('should exclude style attribute from HTML output', () => {
                const attrs = linkComponent.getAttrToHTML();

                expect(attrs.style).toBeUndefined();
            });

            it('should exclude event handler attributes from HTML output', () => {
                linkComponent.addAttributes({onmousedown: 'alert(1)'});

                const attrs = linkComponent.getAttrToHTML();

                expect(attrs.onmousedown).toBeUndefined();
            });
        });

        describe('text trait synchronization', () => {
            let linkComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-text-sync'}
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should have a text trait for link text', () => {
                expect(linkComponent.getTrait('text')).toBeTruthy();
            });

            it('should have a title trait', () => {
                expect(linkComponent.getTrait('title')).toBeTruthy();
            });

            it('should have a target trait', () => {
                expect(linkComponent.getTrait('target')).toBeTruthy();
            });

            it('should have a rel trait', () => {
                expect(linkComponent.getTrait('rel')).toBeTruthy();
            });
        });

        describe('detectAndApply from HTML', () => {
            let linkComponent;

            beforeEach(done => {
                editor.setComponents(
                    '<a href="http://example.com" class="btn" id="test-detect">Click</a>'
                );

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should detect button style from HTML element classes', () => {
                expect(linkComponent.get('linkStyle')).toEqual('button');
            });

            it('should apply detected style classes', () => {
                expect(linkComponent.getClasses()).toContain('btn');
            });
        });

        describe('detectAndApply link with file classes from HTML', () => {
            let linkComponent;

            beforeEach(done => {
                editor.setComponents(
                    '<a href="/file.pdf" class="no-hash link" id="test-detect-file">Download</a>'
                );

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should load as a link component and preserve the no-hash class', () => {
                expect(linkComponent.get('linkStyle')).toEqual('link');
                expect(linkComponent.getClasses()).toContain('no-hash');
            });
        });

        describe('container mode caching', () => {
            let linkComponent;

            beforeEach(done => {
                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-cache'},
                    components: [{
                        type: 'textnode',
                        content: 'Original text'
                    }]
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should cache link text when enabling container mode', () => {
                linkComponent.set('containerMode', true);

                expect(linkComponent.cachedLinkText).toEqual('Original text');
            });

            it('should restore cached text when disabling container mode', () => {
                linkComponent.set('containerMode', true);
                linkComponent.set('containerMode', false);

                const textnode = linkComponent.findType('textnode')[0];

                expect(textnode).toBeDefined();
                expect(textnode.get('content')).toEqual('Original text');
            });
        });

        describe('file selection (applyFileMetadata)', () => {
            let linkComponent;
            let meta;

            const linkText = () => {
                const [textnode] = linkComponent.findType('textnode');

                return textnode ? textnode.get('content') : '';
            };

            beforeEach(done => {
                meta = {};
                // Feed controlled metadata instead of opening the real assets dialog.
                editor.Commands.add('open-digital-assets', {
                    run(ed, sender, opts) {
                        opts.onSelect({get: key => (key === 'previewMetadata' ? meta : undefined)});
                    }
                });

                editor.addComponents([{
                    type: 'link',
                    linkStyle: 'link',
                    attributes: {id: 'test-file-pick'}
                }]);

                linkComponent = editor.Components.getComponents().models[0];
                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('should set href and add the no-hash class (no digital-asset-file)', () => {
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();

                expect(linkComponent.getAttributes().href).toEqual('/files/a.pdf');
                expect(linkComponent.getClasses()).toContain('no-hash');
                expect(linkComponent.getClasses()).not.toContain('digital-asset-file');
            });

            it('should fill text and title from the file when not customized', () => {
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();

                expect(linkText()).toEqual('Doc A');
                expect(linkComponent.getAttributes().title).toEqual('Doc A');
            });

            it('should fall back to the file name when the file has no title', () => {
                meta = {url: '/files/price-list.xlsx', title: ''};
                linkComponent.openFilePicker();

                expect(linkText()).toEqual('price-list.xlsx');
            });

            it('should keep user-entered text', () => {
                linkComponent.getTrait('text').setValue('My text');
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();

                expect(linkText()).toEqual('My text');
            });

            it('should keep user-entered title', () => {
                linkComponent.getTrait('title').setValue('My title');
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();

                expect(linkComponent.getAttributes().title).toEqual('My title');
            });

            it('should always update the href on file selection, even after a manual edit', () => {
                linkComponent.addAttributes({href: 'http://typed-by-hand.example'});
                meta = {url: '/files/x.pdf', title: 'X'};
                linkComponent.openFilePicker();

                expect(linkComponent.getAttributes().href).toEqual('/files/x.pdf');
            });

            it('should not force a target', () => {
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();

                expect(linkComponent.getAttributes().target).toBeUndefined();
            });

            it('should refresh auto-filled text on a second file selection', () => {
                meta = {url: '/files/a.pdf', title: 'Doc A'};
                linkComponent.openFilePicker();
                expect(linkText()).toEqual('Doc A');

                meta = {url: '/files/b.pdf', title: 'Doc B'};
                linkComponent.openFilePicker();
                expect(linkText()).toEqual('Doc B');
            });
        });
    });
});
