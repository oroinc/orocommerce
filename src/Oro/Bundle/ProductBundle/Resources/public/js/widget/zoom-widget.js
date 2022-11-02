define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    require('jquery-elevatezoom');
    require('jquery-ui/widget');

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
            this.options.onZoomedImageLoaded = this._onZoomedImageLoaded.bind(this);

            // Bind activeImage event of slick gallery
            this.element.on('slider:activeImage', (e, activeImage) => {
                if (!this.element.is(activeImage)) {
                    this._updateZoomContainer(activeImage);
                }
            });

            const initImage = this.element.data('slider:activeImage') || this.element.get(0);

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

            for (let i = 0; i < this._zoomedImageLoadedQueue.length; i++) {
                if (typeof this._zoomedImageLoadedQueue[i] === 'function') {
                    this._zoomedImageLoadedQueue[i].call(this);
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
            const imageZoomData = this.element.data('elevateZoom');

            if (!imageZoomData) {
                // Widget was destroyed, nothing to do
                return;
            }

            const imageLargeWidth = imageZoomData.largeWidth;
            const imageLargeHeight = imageZoomData.largeHeight;

            const zoomWindowWidth = this.options.zoomWindowWidth;
            const zoomWindowHeight = this.options.zoomWindowHeight;

            // Check if image has small size
            if (imageLargeWidth <= zoomWindowWidth || imageLargeHeight <= zoomWindowHeight) {
                // Increase large size of small image
                const newImageZoomWidth = imageLargeWidth * 3;
                const newImageZoomHeight = imageLargeHeight * 3;

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
            const imageWidth = $(activeImage).width();
            const imageHeight = $(activeImage).height();

            const zoomWindowHeight = this.options.zoomWindowHeight;

            const maxZoomWindowWidth = this.options.maxZoomWindowWidth;
            const minZoomWindowWidth = this.options.minZoomWindowWidth;

            // Calculate proportional size of zoom window
            let proportionalWidth = zoomWindowHeight * imageWidth / imageHeight;

            // Check max zoom window width
            proportionalWidth = proportionalWidth > maxZoomWindowWidth ? maxZoomWindowWidth : proportionalWidth;

            // Check min zoom window width
            proportionalWidth = proportionalWidth < minZoomWindowWidth ? minZoomWindowWidth : proportionalWidth;

            // Set proportionalWidth for zoom window
            this.options.zoomWindowWidth = proportionalWidth;

            if (_.isRTL()) {
                this.options.zoomWindowOffetx = -proportionalWidth;
            }
        },

        /**
         * Remove all zoom data and DOM elements of zoom widget
         *
         * @private
         */
        _removeZoomContainer: function() {
            const elevateZoom = this.element.data('elevateZoom');

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
