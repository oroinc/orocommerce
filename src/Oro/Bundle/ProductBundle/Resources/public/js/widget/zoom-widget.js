import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import viewportManager from 'oroui/js/viewport-manager';
import tools from 'oroui/js/tools';
import moduleConfig from 'module-config';
import 'jquery-elevatezoom';
import 'jquery-ui/widget';

const config = {
    scrollZoom: true,
    zoomType: 'window',
    tint: false,
    ...Object.fromEntries(Object.entries(moduleConfig(module.id)).filter(([key, value]) => value !== null))
};

$.widget('oroproduct.zoomWidget', {
    options: {
        zoomWindowPosition: 'zoom-container',
        minZoomWindowWidth: 376,
        maxZoomWindowWidth: 700,
        zoomWindowWidth: 376,
        zoomWindowHeight: 520,
        zoomWindowFadeIn: 400,
        borderSize: 1,
        zoomLevel: 1,
        maxZoomLevel: 1,
        borderColour: 'var(--zoom-container-border-color)',
        lensBorderColour: 'var(--zoom-lens-border-color, #7d7d7d)',
        lensColour: 'var(--zoom-lens-color, #000)',
        lensOpacity: 'var(--zoom-lens-opacity, .22)',
        tintColour: 'var(--zoom-tint-color, #333)',
        tintOpacity: 'var(--zoom-tint-opacity, .4)',
        zIndex: 90,
        ...config
    },

    _zoomedImageLoadedState: false,

    imageLoadingClass: 'loading',

    /**
     * Queue for tasks that should be done after elevatezoom loaded
     *
     * @private
     */
    _zoomedImageLoadedQueue: [],

    _init: function() {
        if (tools.isTouchDevice()) {
            return;
        }

        this.options.onZoomedImageLoaded = this._onZoomedImageLoaded.bind(this);

        if (this.options.zoomType === 'inner') {
            this.options.zoomWindowPosition = 1;
            this.options.tint = false;
        }
        // Bind activeImage event of slick gallery
        this.element.on('slider:activeImage', (e, activeImage) => {
            if (!this.element.is(activeImage)) {
                this._updateZoomContainer(activeImage);
            }
        });

        const initImage = this.element.data('slider:activeImage') || this.element.get(0);

        mediator.on('widget:doRefresh', this._reset, this);
        mediator.on('viewport:desktop-small', this.onChangeViewport, this);

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
        this.adjustOriginalImage(activeImage).finally(() => this.element.elevateZoom(this.options));
        this.element.addClass(`image-zoom-${this.options.zoomType}`);
    },

    adjustOriginalImage(activeImage) {
        return new Promise(resolve => {
            const $activeImage = $(activeImage);
            const zoomImageUrl = $activeImage.data('zoom-image');

            if ($activeImage.data('image-adjusted')) {
                return resolve();
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const image = new Image();

            image.onload = () => {
                const {naturalHeight: previewNaturalHeight, naturalWidth: previewNaturalWidth} = activeImage;
                const originAspectRatio = previewNaturalWidth / previewNaturalHeight;
                const aspectRatio = image.naturalWidth / image.naturalHeight;

                if (aspectRatio - originAspectRatio > 0) {
                    ctx.canvas.width = image.naturalWidth;
                    ctx.canvas.height = image.naturalWidth / originAspectRatio;
                } else {
                    ctx.canvas.width = image.naturalHeight * originAspectRatio;
                    ctx.canvas.height = image.naturalHeight;
                }

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(image,
                    (ctx.canvas.width - image.naturalWidth) / 2,
                    (ctx.canvas.height - image.naturalHeight) / 2,
                    image.naturalWidth,
                    image.naturalHeight
                );

                $activeImage.attr('original-zoom-image', $activeImage.data('zoom-image'));
                $activeImage.data('zoom-image', canvas.toDataURL('image/webp'));
                $activeImage.data('image-adjusted', true);

                resolve();

                canvas.remove();
            };

            image.src = zoomImageUrl;
        });
    },

    /**
     * Update zoom container
     * @param initImage
     * @private
     */
    _updateZoomContainer: function(initImage) {
        initImage.classList.add(this.imageLoadingClass);

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
        if (viewportManager.isApplicable('desktop-small')) {
            this._removeZoomContainer();
            this._zoomInit(initImage);
        }
    },

    _reset: function() {
        this._updateZoomContainer(this.element.get(0));
    },

    onChangeViewport(viewport) {
        if (!viewportManager.isApplicable('desktop-small')) {
            this._destroy();
        } else {
            this._reInitZoomContainer(this.element.get(0));
        }
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
            this.deleteZoomContainer();
        } else if (
            imageZoomData.options.zoomType === 'window' && imageZoomData.options.scrollZoom &&
            (imageLargeWidth <= zoomWindowWidth * 2 && imageLargeHeight <= zoomWindowHeight * 2)
        ) {
            imageZoomData.options.maxZoomLevel = 0.6;
            imageZoomData.options.minZoomLevel = 0.2;
            imageZoomData.options.zoomLevel = 0.6;

            this.deleteZoomContainer();

            imageZoomData.startZoom();
        }

        if (imageZoomData.zoomWindow) {
            imageZoomData.zoomWindow.css('z-index', this.options.zIndex);
        }

        this.element.removeClass(this.imageLoadingClass);
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

    deleteZoomContainer() {
        // Should remove old zoom containers from DOM
        const elevateZoom = this.element.data('elevateZoom');

        if (elevateZoom && elevateZoom.zoomContainer) {
            $('.zoomContainer').remove();
            $('.zoomWindowContainer').remove();
        }
    },

    /**
     * Remove all zoom data and DOM elements of zoom widget
     *
     * @private
     */
    _removeZoomContainer: function() {
        this.deleteZoomContainer();

        this.element.removeData('elevateZoom');
        this.element.removeClass(`image-zoom-${this.options.zoomType}`);
        this._unBindZoomEvents();
    },

    /**
     * Remove zoom container events
     * @private
     */
    _unBindZoomEvents: function() {
        this.element.off('mousemove touchmove touchend mousewheel DOMMouseScroll MozMousePixelScroll');
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

export default 'zoomWidget';
