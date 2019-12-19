define(function(require) {
    'use strict';

    const PopupGalleryWidget = require('orofrontend/js/app/components/popup-gallery-widget');
    const $ = require('jquery');
    const _ = require('underscore');

    const ProductPopupGalleryWidget = PopupGalleryWidget.extend({
        /**
         * @inheritDoc
         */
        constructor: function PopupGalleryWidget(options) {
            ProductPopupGalleryWidget.__super__.constructor.call(this, options);
        },

        setDependentSlide: function(e) {
            if (e) {
                var index = $(e.currentTarget).data('gallery-image-index');
                this.$gallery.slick('slickGoTo', index, true);
                if (this.useThumb()) {
                    this.$thumbnails.slick('slickGoTo', index, true);
                }
            }

            ProductPopupGalleryWidget.__super__.setDependentSlide.call(this, e);
        }
    });

    return ProductPopupGalleryWidget;
});
