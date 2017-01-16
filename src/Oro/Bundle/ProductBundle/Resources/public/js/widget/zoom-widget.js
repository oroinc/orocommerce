define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('jquery-elevatezoom');

    $.widget('oroproduct.zoomWidget', {
        options: {
            scrollZoom: true,
            zoomWindowWidth: 630,
            zoomWindowHeight: 376,
            borderSize: 1,
            borderColour: '#ebebeb',
            lensBorderColour: '#7d7d7d',
            lensColour: '#000',
            lensOpacity: 0.22
        },

        initialized: false,

        _init: function() {
            var self = this;

            this._setActiveImage();

            this.element.elevateZoom(this.options);

            this.element.on('slider:activeImage', function(e, activeImage) {
                $(this).data('zoom-image', $(activeImage).attr('data-url-zoom'));
            });

            this.element.on('slider:beforeChange', function(e) {
                self._destroy();
                self._init();
            });
        },

        _setActiveImage: function () {
            var self = this;
            var dependentSlider = this.options.dependentSlider;
            if (dependentSlider && self.initialized) {
                var activeImageIndex = $(dependentSlider).slick('slickCurrentSlide');
                var activeImage = $(dependentSlider).find('.slick-slide[data-slick-index=' + activeImageIndex + '] img');
                self.element.data('zoom-image', $(activeImage).attr('data-url-zoom'));
                self.initialized = true;
            }
        },

        _destroy: function() {
            this.element.off('slider:beforeChange');
            var elevateZoom = this.element.data('elevateZoom');
            if (elevateZoom && elevateZoom.zoomContainer) {
                elevateZoom.zoomContainer.remove();// remove zoom container from DOM
            }
            $.removeData(this.element, 'elevateZoom');//remove zoom instance from element
        }
    });

    return 'zoomWidget';
});
