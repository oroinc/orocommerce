import 'jquery.validate';
import 'jasmine-jquery';
import 'oroui/js/app/modules/input-widgets';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import html from 'text-loader!./fixtures/grapesjs-editor-view-fixture.html';
import './fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/grapesjs-editor-view', () => {
    let grapesjsEditorView;

    beforeEach(done => {
        window.setFixtures(html);
        grapesjsEditorView = new GrapesjsEditorView({
            el: '#grapesjs-view',
            themes: [{
                label: 'Test',
                stylesheet: '',
                active: true
            }],
            disableDeviceManager: true
        });

        grapesjsEditorView.builder.on('editor:rendered', () => done());
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('view "GrapesjsEditorView"', () => {
        it('is view defined', () => {
            expect(grapesjsEditorView).toBeTruthy();
            expect(grapesjsEditorView).toBeInstanceOf(GrapesjsEditorView);
        });

        it('check default content', () => {
            expect(grapesjsEditorView.builder.getHtml()).toEqual('<div>Default content</div>');
        });

        it('check container should be created', () => {
            expect(grapesjsEditorView.$container).toBeMatchedBy('.grapesjs.gjs-editor-cont');
        });

        it('check "ComponentRestriction" should be defined', () => {
            expect(grapesjsEditorView.builder.ComponentRestriction).toBeDefined();
        });

        it('check "RteEditor" should be defined', () => {
            expect(grapesjsEditorView.builder.RteEditor).toBeDefined();
            expect(grapesjsEditorView.builder.RteEditor.collection).toBeDefined();
        });

        it('check "CodeValidator" should be defined', () => {
            expect(grapesjsEditorView.builder.CodeValidator).toBeDefined();
        });
    });
});
