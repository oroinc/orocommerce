define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('oroproduct/js/vendors/elevatezoom/jquery-elevatezoom');

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

        _init: function() {
            this.element.elevateZoom(this.options);
            var self = this;
            this.element.on('slider:activeImage', function(e, activeImage) {
                $(this).data('zoom-image', $(activeImage).attr('data-url-zoom'));
                self._destroy();
                self._init();
            });
        },

        _destroy: function() {
            this.element.off('slider:activeImage');
            var elevateZoom = this.element.data('elevateZoom');
            if (elevateZoom && elevateZoom.zoomContainer) {
                elevateZoom.zoomContainer.remove();// remove zoom container from DOM
            }
            $.removeData(this.element, 'elevateZoom');//remove zoom instance from element
        }
    });

    return 'zoomWidget';
});
