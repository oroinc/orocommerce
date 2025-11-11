import PopupGalleryWidget from 'orofrontend/js/app/components/popup-gallery-widget';
import $ from 'jquery';

const ProductPopupGalleryWidget = PopupGalleryWidget.extend({
    /**
     * @inheritdoc
     */
    constructor: function PopupGalleryWidget(options) {
        ProductPopupGalleryWidget.__super__.constructor.call(this, options);
    },

    setDependentSlide: function(e) {
        if (e) {
            const index = $(e.currentTarget).data('gallery-image-index');
            this.$gallery.slick('slickGoTo', index, true);
            if (this.useThumb()) {
                this.$thumbnails.slick('slickGoTo', index, true);
            }
        }

        ProductPopupGalleryWidget.__super__.setDependentSlide.call(this, e);
    }
});

export default ProductPopupGalleryWidget;
