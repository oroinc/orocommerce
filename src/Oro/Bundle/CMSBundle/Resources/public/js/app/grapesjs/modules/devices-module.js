define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var viewportManager = require('oroui/js/viewport-manager');

    /**
     * Create panel manager instance
     * @param options
     * @constructor
     */
    var DevicesModule = function(options) {
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
            this.$builderIframe = this.builder.Canvas.getFrameEl();
            this.canvasEl = this.builder.Canvas.getElement();

            this.initButtons();
            this.builder.on('changeTheme', _.bind(this.initButtons, this));
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
            var frameHead = this.$builderIframe.contentDocument.head;
            var breakpoints = mediator.execute('fetch:head:computedVars', frameHead);

            this.breakpoints = _.filter(viewportManager._collectCSSBreakpoints(breakpoints), function(breakpoint) {
                return breakpoint.name.indexOf('strict') === -1;
            });
        },

        getBreakpoints: function() {
            var defer = $.Deferred();
            var inter = setInterval(_.bind(function() {
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
            var devicePanel = this.builder.Panels.getPanel('devices-c');
            var deviceButton = devicePanel.get('buttons');
            var DeviceManager = this.builder.DeviceManager;
            var Commands = this.builder.Commands;

            deviceButton.reset();
            DeviceManager.getAll().reset();

            Commands.add('setDevice', {
                run: function(editor, sender) {
                    editor.setDevice(sender.id);
                    var canvas = editor.Canvas.getElement();

                    canvas.classList.add(sender.id);
                },
                stop: function(editor, sender) {
                    var canvas = editor.Canvas.getElement();

                    canvas.classList.remove(sender.id);
                }
            });

            _.each(this.breakpoints, function(breakpoint) {
                if (this.canvasEl.classList.length === 1 && breakpoint.name === 'desktop') {
                    this.canvasEl.classList.add(breakpoint.name);
                }

                var width = breakpoint.max ? breakpoint.max + 'px' : false;
                width = this.calculateDeviceWidth(width);
                var options = {
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
            var ratio = width <= 640 ? 1.7 : 1.3;
            var height = invert ? width / ratio : width * ratio;
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
            var str = breakpoint.name + ' view';

            if (breakpoint.max) {
                str += ': ' + breakpoint.max;
            }

            if (options.height) {
                str += 'x' + options.height;
            }

            return str;
        }
    };

    return DevicesModule;
});
