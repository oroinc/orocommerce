define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    require('jquery-elevatezoom');

    $.widget('oroproduct.zoomWidget', {
        options: {
            scrollZoom: true,
            zoomWindowPosition: 'zoom-container',
            minZoomWindowWidth: 376,
            maxZoomWindowWidth: 700,
            zoomWindowWidth: 376,
            zoomWindowHeight: 376,
            zoomWindowFadeIn: 400,
            zoomLevel: 0.4,
            maxZoomLevel: 0.8,
            borderSize: 1,
            borderColour: '#ebebeb',
            lensBorderColour: '#7d7d7d',
            lensColour: '#000',
            lensOpacity: 0.22
        },

        _zoomedImageLoadedState: false,

        /**
         * Queue for tasks that should be done after elevatezoom loaded
         *
         * @private
         */
        _zoomedImageLoadedQueue: [],

        _init: function() {
            this.options.onZoomedImageLoaded = _.bind(this._onZoomedImageLoaded, this);

            // Bind activeImage event of slick gallery
            this.element.on('slider:activeImage', _.bind(function(e, activeImage) {
                if (!this.element.is(activeImage)) {
                    this._updateZoomContainer(activeImage);
                }
            }, this));

            var initImage = this.element.data('slider:activeImage') || this.element.get(0);

            mediator.on('widget:doRefresh', this._reset, this);

            this._ensureLoadedZoomInit(initImage);
        },

        /**
         * Inits of elevatezoom on image that completely loaded
         *
         * @param {HTMLElement} activeImage
         * @private
         */
        _ensureLoadedZoomInit: function(activeImage) {
            if (activeImage.complete) {
                this._zoomInit(activeImage);
            } else {
                $(activeImage).one('load', this._zoomInit.bind(this, activeImage));
            }
        },

        /**
         * Init of elevatezoom and set needed options
         *
         * @param {HTMLElement} activeImage
         * @private
         */
        _zoomInit: function(activeImage) {
            this.element = $(activeImage);
            this._setZoomWindowSize(activeImage);
            this.element.elevateZoom(this.options);
        },

        /**
         * Update zoom container
         * @param initImage
         * @private
         */
        _updateZoomContainer: function(initImage) {
            if (this._zoomedImageLoadedState) {
                this._reInitZoomContainer(initImage);
            } else {
                this._zoomedImageLoadedQueue.push(function() {
                    this._reInitZoomContainer(initImage);
                });
            }
        },

        /**
         * ReInit zoom container
         * @param initImage
         * @private
         */
        _reInitZoomContainer: function(initImage) {
            this._removeZoomContainer();
            this._zoomInit(initImage);
        },

        _reset: function() {
            this._updateZoomContainer(this.element.get(0));
        },

        /**
         * Implementation ZoomedImageLoaded event
         *
         * @private
         */
        _onZoomedImageLoaded: function() {
            this._zoomedImageLoadedState = true;

            for (var i = 0; i < this._zoomedImageLoadedQueue.length; i++) {
                if (typeof this._zoomedImageLoadedQueue[i] === 'function') {
                    _.bind(this._zoomedImageLoadedQueue[i], this)();
                }
            }

            this._zoomedImageLoadedQueue = [];

            this._resetImageProperty();
        },

        /**
         * Reset image property for correct works with small images
         *
         * @private
         */
        _resetImageProperty: function() {
            var imageZoomData = this.element.data('elevateZoom');

            if (!imageZoomData) {
                // Widget was destroyed, nothing to do
                return;
            }

            var imageLargeWidth = imageZoomData.largeWidth;
            var imageLargeHeight = imageZoomData.largeHeight;

            var zoomWindowWidth = this.options.zoomWindowWidth;
            var zoomWindowHeight = this.options.zoomWindowHeight;

            // Check if image has small size
            if (imageLargeWidth <= zoomWindowWidth || imageLargeHeight <= zoomWindowHeight) {
                // Increase large size of small image
                var newImageZoomWidth = imageLargeWidth * 3;
                var newImageZoomHeight = imageLargeHeight * 3;

                imageZoomData.largeWidth = newImageZoomWidth;
                imageZoomData.largeHeight = newImageZoomHeight;

                // Should remove old zoom containers from DOM
                $('.zoomWindowContainer').remove();
                $('.zoomContainer').remove();

                // Call to internal method of elevatezoom for reinit zoom containers
                imageZoomData.startZoom();
            }
        },

        /**
         * Set size of zoom window
         *
         * @param {HTMLElement} activeImage
         * @private
         */
        _setZoomWindowSize: function(activeImage) {
            var imageWidth = $(activeImage).width();
            var imageHeight = $(activeImage).height();

            var zoomWindowHeight = this.options.zoomWindowHeight;

            var maxZoomWindowWidth = this.options.maxZoomWindowWidth;
            var minZoomWindowWidth = this.options.minZoomWindowWidth;

            // Calculate proportional size of zoom window
            var proportionalWidth = zoomWindowHeight * imageWidth / imageHeight;

            // Check max zoom window width
            proportionalWidth = proportionalWidth > maxZoomWindowWidth ? maxZoomWindowWidth : proportionalWidth;

            // Check min zoom window width
            proportionalWidth = proportionalWidth < minZoomWindowWidth ? minZoomWindowWidth : proportionalWidth;

            // Set proportionalWidth for zoom window
            this.options.zoomWindowWidth = proportionalWidth;
        },

        /**
         * Remove all zoom data and DOM elements of zoom widget
         *
         * @private
         */
        _removeZoomContainer: function() {
            var elevateZoom = this.element.data('elevateZoom');

            if (elevateZoom && elevateZoom.zoomContainer) {
                elevateZoom.zoomContainer.remove();
                elevateZoom.zoomWindow.parent().remove();
            }

            this.element.removeData('elevateZoom');
            this.element.removeData('zoomImage');
            this._unBindZoomEvents();
        },

        /**
         * Remove zoom container events
         * @private
         */
        _unBindZoomEvents: function() {
            this.element.unbind('mousemove touchmove touchend mousewheel DOMMouseScroll MozMousePixelScroll');
        },

        /**
         * Remove all zoom data from instans and unbind all zoom events
         *
         * @private
         */
        _destroy: function() {
            this._removeZoomContainer();
            $('.' + this.element[0].className).off('slider:activeImage');
            this._unBindZoomEvents();
        }
    });

    return 'zoomWidget';
});
