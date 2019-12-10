define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const viewportManager = require('oroui/js/viewport-manager');

    /**
     * Create panel manager instance
     * @param options
     * @constructor
     */
    const DevicesModule = function(options) {
        _.extend(this, _.pick(options, ['builder']));

        this.init();
    };

    DevicesModule.prototype = {
        /**
         * @property {DOM.Element}
         */
        $builderIframe: null,

        /**
         * @property {Object}
         */
        breakpoints: {},

        /**
         * @property {DOM.Element}
         */
        canvasEl: null,

        /**
         * Run device manager
         */
        init: function() {
            this.rte = this.builder.RichTextEditor;
            this.canvasEl = this.builder.Canvas.getElement();
            this.$builderIframe = this.builder.Canvas.getFrameEl();

            this.initButtons();
            this.builder.on('changeTheme', _.bind(this.initButtons, this));
            this.builder.on('rteToolbarPosUpdate', _.bind(this.updateRtePosition, this));
        },

        initButtons: function() {
            this.getBreakpoints().then(_.bind(function() {
                this.createButtons();
            }, this));
        },

        /**
         * Fetch brakpoints from theme stylesheet
         * @private
         */
        _getCSSBreakpoint: function() {
            const frameHead = this.$builderIframe.contentDocument.head;
            const breakpoints = mediator.execute('fetch:head:computedVars', frameHead);

            this.breakpoints = _.filter(viewportManager._collectCSSBreakpoints(breakpoints), function(breakpoint) {
                return breakpoint.name.indexOf('strict') === -1;
            });
        },

        getBreakpoints: function() {
            const defer = $.Deferred();
            const inter = setInterval(_.bind(function() {
                this._getCSSBreakpoint();

                if (this.breakpoints.length) {
                    clearInterval(inter);
                    defer.resolve();
                }
            }, this), 50);

            return defer.promise();
        },

        /**
         * Create buttons controls via breakpoints
         */
        createButtons: function() {
            const devicePanel = this.builder.Panels.getPanel('devices-c');
            const deviceButton = devicePanel.get('buttons');
            const DeviceManager = this.builder.DeviceManager;
            const Commands = this.builder.Commands;

            deviceButton.reset();
            DeviceManager.getAll().reset();

            Commands.add('setDevice', {
                run: function(editor, sender) {
                    editor.setDevice(sender.id);
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.add(sender.id);
                },
                stop: function(editor, sender) {
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.remove(sender.id);
                }
            });

            _.each(this.breakpoints, function(breakpoint) {
                if (this.canvasEl.classList.length === 1 && breakpoint.name === 'desktop') {
                    this.canvasEl.classList.add(breakpoint.name);
                }

                let width = breakpoint.max ? breakpoint.max + 'px' : false;
                width = this.calculateDeviceWidth(width);
                let options = {
                    height: this.calculateDeviceHeight(width)
                };

                if (breakpoint.name.indexOf('landscape') !== -1) {
                    options = {
                        height: this.calculateDeviceHeight(width, true),
                        widthMedia: width
                    };
                }

                DeviceManager.add(breakpoint.name, width, options);

                deviceButton.add({
                    id: breakpoint.name,
                    command: 'setDevice',
                    togglable: false,
                    className: breakpoint.name,
                    active: breakpoint.name === 'desktop',
                    attributes: {
                        'data-toggle': 'tooltip',
                        'title': this.concatTitle(breakpoint, options)
                    }
                });

                $(devicePanel.view.$el.find('[data-toggle="tooltip"]')).tooltip();
            }, this);
        },

        /**
         * Calculate device height
         * @param width
         * @param invert
         * @returns {string}
         */
        calculateDeviceHeight: function(width, invert) {
            if (!width) {
                return '';
            }

            width = parseInt(width);

            if (!invert) {
                invert = false;
            }
            const ratio = width <= 640 ? 1.7 : 1.3;
            let height = invert ? width / ratio : width * ratio;
            if (height > this.canvasEl.offsetHeight) {
                height = this.canvasEl.offsetHeight;
            }
            return Math.round(height) + 'px';
        },

        calculateDeviceWidth: function(width, invert) {
            if (!width) {
                return '';
            }

            width = parseInt(width);
            if (width > this.canvasEl.offsetWidth - 100) {
                width = this.canvasEl.offsetWidth - 100;
            }
            return width + 'px';
        },

        /**
         * Concat title device
         * @param breakpoint
         * @param options
         * @returns {string}
         */
        concatTitle: function(breakpoint, options) {
            let str = breakpoint.name + ' view';

            if (breakpoint.max) {
                str += ': ' + breakpoint.max;
            }

            if (options.height) {
                str += 'x' + options.height;
            }

            return str;
        },

        updateRtePosition: function(pos) {
            if (pos.targetHeight !== 0) {
                const style = window.getComputedStyle(this.$builderIframe);
                const borderTopSize = parseInt(style['border-top-width']);
                const borderLeftSize = parseInt(style['border-left-width']);
                const rteActionBarWidth = $(this.rte.actionbar).innerWidth();
                const builderIframeWidth = $(this.$builderIframe).innerWidth();
                let positionLeft = pos.left;

                if (builderIframeWidth <= (pos.left + pos.targetWidth)) {
                    positionLeft = pos.elementLeft + pos.elementWidth - rteActionBarWidth;
                }

                pos.left = positionLeft += borderLeftSize;
                pos.top = pos.elementTop + pos.elementHeight + borderTopSize;
            }
        }
    };

    return DevicesModule;
});
