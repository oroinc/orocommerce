import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import VideoTypeBuilder from 'orocms/js/app/grapesjs/type-builders/video-type-builder';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/type-builders/video-type-builder', () => {
    let videoTypeBuilder;
    let editor;

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

    describe('component "VideoTypeBuilder"', () => {
        beforeEach(() => {
            videoTypeBuilder = new VideoTypeBuilder({
                editor,
                componentType: 'video'
            });

            videoTypeBuilder.execute();
        });

        afterEach(() => {
            videoTypeBuilder.dispose();
        });

        it('check to be defined', () => {
            expect(videoTypeBuilder.componentType).toEqual('video');
        });

        it('check is component type defined', () => {
            const type = videoTypeBuilder.editor.DomComponents.getType('video');
            expect(type).toEqual(jasmine.objectContaining({
                id: 'video'
            }));
        });

        it('check component parent type', () => {
            expect(videoTypeBuilder.parentType).toEqual('video');
        });

        it('check base model extend', () => {
            const mockElement = document.createElement('VIDEO');

            expect(videoTypeBuilder.Model.isComponent).toEqual(jasmine.any(Function));
            expect(videoTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: videoTypeBuilder.componentType,
                initial: true,
                controls: 0,
                loop: 0,
                autoplay: 0,
                poster: ''
            });

            mockElement.setAttribute('loop', true);
            mockElement.setAttribute('autoplay', true);
            expect(videoTypeBuilder.Model.isComponent(mockElement)).toEqual({
                type: videoTypeBuilder.componentType,
                initial: true,
                controls: 0,
                loop: 1,
                autoplay: 1,
                poster: ''
            });
            expect(videoTypeBuilder.Model.componentType).toEqual(videoTypeBuilder.componentType);

            expect(videoTypeBuilder.Model.prototype.defaults.tagName).toEqual('video');
            expect(videoTypeBuilder.Model.prototype.defaults.style).toEqual({
                height: '400px',
                width: '100%'
            });

            expect(videoTypeBuilder.Model.prototype.editor).toEqual(editor);
        });
    });
});
