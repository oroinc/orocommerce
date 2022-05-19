import PictureTypeSettingsView from 'orocms/js/app/grapesjs/controls/picture-settings';

describe('orocms/js/app/grapesjs/controls/picture-settings "PictureTypeSettingsView" model', () => {
    let pictureTypeSettingsView;

    const PictureTypeSettingsViewTest = PictureTypeSettingsView.extend({
        constructor: function PictureTypeSettingsViewTest(...args) {
            PictureTypeSettingsViewTest.__super__.constructor.apply(this, args);
        },
        validate() {}
    });

    beforeEach(() => {
        pictureTypeSettingsView = new PictureTypeSettingsViewTest({
            props: {
                sources: [
                    {
                        attributes: {
                            srcset: 'http://path/wysiwyg_original/example-image.jpeg'
                        }
                    },
                    {
                        attributes: {
                            srcset: 'http://otherpath/wysiwyg_original/example-image.webp'
                        }
                    },
                    {
                        attributes: {
                            srcset: 'http://example-image.png',
                            media: '(max-width: 1000px)'
                        }
                    }
                ],
                mainImage: {
                    attributes: {
                        src: 'http://path/wysiwyg_original/example-image.jpeg'
                    }
                }
            },
            editor: {
                getBreakpoints() {
                    return [{
                        height: '1359px',
                        max: 1099,
                        name: 'tablet',
                        widthDevice: '1045px'
                    }, {
                        height: '997px',
                        max: 767,
                        name: 'mobile-big',
                        widthDevice: '767px'
                    }, {
                        height: '376px',
                        max: 640,
                        name: 'mobile-landscape',
                        widthDevice: '640px'
                    }];
                }
            },
            dialog: {
                blockSaveButton() {}
            }
        });
    });

    afterEach(() => {
        pictureTypeSettingsView.dispose();
    });

    it('check initialize', () => {
        expect(pictureTypeSettingsView.validateApiAccessor.initialOptions).toEqual({
            http_method: 'POST',
            route: 'oro_cms_wysiwyg_content_validate'
        });
        expect(pictureTypeSettingsView.subview('sourceCollection')).toBeDefined();
    });

    it('check render', () => {
        expect(pictureTypeSettingsView.$('tbody > tr').length).toEqual(4);
    });

    it('check remove item', () => {
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.length).toEqual(4);
        pictureTypeSettingsView.$('tbody tr:eq(1) .removeRow').trigger('click');
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.length).toEqual(3);
    });

    it('check "getTempContent"', () => {
        // eslint-disable-next-line
        expect(pictureTypeSettingsView.getTempContent()).toEqual(`<picture><source srcset="http://path/wysiwyg_original/example-image.jpeg" type="image/jpeg" >
<source srcset="http://otherpath/wysiwyg_original/example-image.webp" type="image/webp" >
<source srcset="http://example-image.png" media="(max-width: 1000px)" type="image/png" >
<img src="http://path/wysiwyg_original/example-image.jpeg" >
</picture>`);
    });

    it('check update after sort', () => {
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.at(2).toJSON()).toEqual({
            attributes: {
                media: '(max-width: 1000px)',
                srcset: 'http://example-image.png',
                type: 'image/png'
            },
            preview: 'http://example-image.png',
            invalid: false,
            errorMessage: '',
            main: false,
            index: 2,
            sortable: true
        });
        const tr = pictureTypeSettingsView.$('tbody tr:eq(2)');
        tr.prependTo(pictureTypeSettingsView.$('tbody'));

        pictureTypeSettingsView.subview('sourceCollection').onSort({
            currentTarget: pictureTypeSettingsView.$('tbody')[0]
        });
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.at(0).toJSON()).toEqual({
            attributes: {
                media: '(max-width: 1000px)',
                srcset: 'http://example-image.png',
                type: 'image/png'
            },
            preview: 'http://example-image.png',
            invalid: false,
            errorMessage: '',
            main: false,
            index: 0,
            sortable: true
        });
    });

    it('check add source', () => {
        pictureTypeSettingsView.subview('sourceCollection').collection.add({
            attributes: {
                srcset: 'http://otherpath/wysiwyg_original/example-test-image.webp',
                type: 'image/webp'
            },
            index: pictureTypeSettingsView.subview('sourceCollection').collection.length - 1
        });

        expect(pictureTypeSettingsView.subview('sourceCollection').collection.length).toEqual(5);
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.at(3).toJSON()).toEqual({
            attributes: {
                srcset: 'http://otherpath/wysiwyg_original/example-test-image.webp',
                type: 'image/webp'
            },
            preview: 'http://otherpath/digital_asset_in_dialog/example-test-image.webp',
            invalid: false,
            errorMessage: '',
            main: false,
            index: 3,
            sortable: true
        });
    });

    it('check breakpoints', () => {
        expect(pictureTypeSettingsView.$('tbody tr:eq(0) .dropdown-item').length).toEqual(3);

        pictureTypeSettingsView.$('tbody tr:eq(0) .dropdown .btn').trigger('click');
        pictureTypeSettingsView.$('tbody tr:eq(0) .dropdown-item:eq(2)').trigger('click');
        pictureTypeSettingsView.$('tbody tr:eq(0) input[name="media"]').trigger('change');
        expect(pictureTypeSettingsView.subview('sourceCollection').collection.at(0).toJSON()).toEqual({
            attributes: {
                media: '(max-width: 640px) and (orientation: landscape)',
                srcset: 'http://path/wysiwyg_original/example-image.jpeg',
                type: 'image/jpeg'
            },
            errorMessage: '',
            index: 0,
            invalid: false,
            main: false,
            preview: 'http://path/digital_asset_in_dialog/example-image.jpeg',
            sortable: true
        });
    });
});
