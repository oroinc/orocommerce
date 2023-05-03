import 'jasmine-jquery';
import grapesJS from 'grapesjs';
import VideoTypeBuilder from 'orocms/js/app/grapesjs/types/video';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/types/video', () => {
    let videoTypeBuilder;
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

        editor.on('load', () => done());
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

        it('check is component type button', () => {
            const button = videoTypeBuilder.editor.BlockManager.get(videoTypeBuilder.componentType);
            expect(button.get('content').style).toEqual({
                height: '400px',
                width: '100%'
            });
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

            expect(videoTypeBuilder.Model.prototype.editor).toEqual(editor);
        });

        describe('test type in editor scope', () => {
            let videoComponent;
            beforeEach(done => {
                editor.Components.getComponents().reset([{
                    type: 'video',
                    initial: true
                }], {
                    silent: true
                });

                videoComponent = editor.Components.getComponents().models[0];

                setTimeout(() => done(), 0);
            });

            afterEach(done => {
                editor.setComponents([]);
                setTimeout(() => done(), 0);
            });

            it('check "toHTML" defaults', () => {
                expect(videoComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<video src="" controls="controls"></video>'
                );
            });

            it('check "toHTML" html5 provider', () => {
                videoComponent.set({
                    loop: 1,
                    muted: 1,
                    autoplay: 1,
                    controls: 1,
                    src: 'http://video.url/video',
                    poster: 'http://poster.loc/image'
                });

                expect(videoComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<video src="http://video.url/video" poster="http://poster.loc/image" loop="loop" autoplay="autoplay" controls="controls"></video>'
                );
            });

            it('check "toHTML" youtube provider', () => {
                videoComponent.set({
                    provider: 'yt',
                    loop: 1,
                    muted: 1,
                    autoplay: 1,
                    controls: 1,
                    videoId: 'https://youtu.be/MLpWrANjFbI'
                });

                expect(videoComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<iframe src="https://www.youtube.com/embed/MLpWrANjFbI?&autoplay=1&loop=1&playlist=MLpWrANjFbI&mute=1" allowfullscreen="allowfullscreen"></iframe>'
                );
            });

            it('check "toHTML" youtube without cookie provider', () => {
                videoComponent.set({
                    provider: 'ytnc',
                    loop: 1,
                    muted: 1,
                    autoplay: 1,
                    controls: 1,
                    videoId: 'https://youtu.be/MLpWrANjFbI'
                });

                expect(videoComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<iframe src="https://www.youtube-nocookie.com/embed/MLpWrANjFbI?&autoplay=1&loop=1&playlist=MLpWrANjFbI&mute=1" allowfullscreen="allowfullscreen"></iframe>'
                );
            });

            it('check "toHTML" vimeo provider', () => {
                videoComponent.set({
                    provider: 'vi',
                    loop: 1,
                    muted: 1,
                    autoplay: 1,
                    controls: 1,
                    videoId: 'https://vimeo.com/163721649'
                });

                expect(videoComponent.toHTML()).toEqual(
                    // eslint-disable-next-line
                    '<iframe src="https://player.vimeo.com/video/163721649?&autoplay=1&loop=1&muted=1" allowfullscreen="allowfullscreen"></iframe>'
                );
            });
        });
    });
});
