import 'jasmine-jquery';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import Validator from 'orocms/js/app/grapesjs/validation';
import '../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/validation', () => {
    let validator;
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

    describe('module "Validator"', () => {
        beforeEach(() => {
            validator = new Validator({
                editor: grapesjsEditorView.builder
            });
        });

        it('check "idCollision"', () => {
            expect(validator.validate(`<div>
                    <div id="test"></div>
                    <div id="test-1">
                        test content
                        <span id="test"></span>
                    </div>
                    <p>Test content</p>
                    <div id="other-id">
                        <div id="test"></div>
                    </div>
                </div>`)).toEqual([
                {
                    line: 5,
                    shortMessage: '',
                    message: 'oro.htmlpurifier.formatted_error_line'
                },
                {
                    line: 9,
                    shortMessage: '',
                    message: 'oro.htmlpurifier.formatted_error_line'
                }
            ]);
        });

        it('check "reservedId"', () => {
            expect(validator.validate(`<div>
                    <div id="isolation-scope-test432423"></div>
                    <div id="test-1">
                        test content
                        <span id="test"></span>
                    </div>
                    <p>Test content</p>
                    <div id="other-id">
                        <div id="isolation-scope-test12355"></div>
                    </div>
                </div>`)).toEqual([
                {
                    line: 2,
                    shortMessage: '',
                    message: 'oro.htmlpurifier.formatted_error_line'
                },
                {
                    line: 9,
                    shortMessage: '',
                    message: 'oro.htmlpurifier.formatted_error_line'
                }
            ]);
        });

        it('check "cssValidation"', () => {
            expect(validator.validate(`<div>test</div><style>.test  color red}</style>`)).toEqual([
                {
                    line: 1,
                    shortMessage: 'oro.htmlpurifier.messages.invalid_styles_short',
                    message: 'oro.htmlpurifier.formatted_error_line'
                }
            ]);

            expect(validator.validate(`<div>test</div>
                        <style>.test { color red}</style>
                        <style>#test { background green }</style>`))
                .toEqual([{
                    line: 2,
                    shortMessage: 'oro.htmlpurifier.messages.invalid_styles_short',
                    message: 'oro.htmlpurifier.formatted_error_line'
                }, {
                    line: 3,
                    shortMessage: 'oro.htmlpurifier.messages.invalid_styles_short',
                    message: 'oro.htmlpurifier.formatted_error_line'
                }]);
        });

        it('check "prepareErrorData"', () => {
            expect(validator.prepareErrorData(2, 'Test sub message', {
                lineMessage: 'Test line message',
                shortMessage: 'Test short message'
            })).toEqual({
                line: 2,
                message: 'Test line message',
                shortMessage: 'Test short message'
            });

            expect(validator.prepareErrorData(2, 'Test sub message', {
                shortMessage: 'Test short message'
            })).toEqual({
                line: 2,
                message: 'oro.htmlpurifier.formatted_error_line',
                shortMessage: 'Test short message'
            });
        });
    });
});
