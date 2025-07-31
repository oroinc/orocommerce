define(function(require) {
    'use strict';

    const BaseClass = require('oroui/js/base-class');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const viewportManager = require('oroui/js/viewport-manager').default;
    const __ = require('orotranslation/js/translator');
    const {getLegacyBreakpoints} = require('../utils/legacy');

    /**
     * Create panel manager instance
     */
    const DevicesModule = BaseClass.extend({
        /**
         * @property {DOM.Element}
         */
        $builderIframe: null,

        /**
         * @property {Object}
         */
        breakpoints: {},

        MAX_STYLE_REQUEST_TRIES: 100,

        allowBreakpoints: [
            'desktop',
            'tablet',
            'tablet-small',
            'mobile-big',
            'mobile-landscape',
            'mobile',
            'mobile-small'
        ],

        /**
         * @property {DOM.Element}
         */
        canvasEl: null,

        /**
         * @inheritdoc
         */
        constructor: function DevicesModule(options) {
            DevicesModule.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            if (!options.builder) {
                throw new Error('Required option builder not found.');
            } else {
                this.builder = options.builder;
            }

            if (options.allowBreakpoints.length) {
                this.allowBreakpoints = options.allowBreakpoints;
            }

            this._deferredInit();

            const {Commands, Canvas} = this.builder;

            this.canvasEl = Canvas.getElement();
            this.$builderIframe = $(Canvas.getFrameEl());
            this.$framesArea = $(Canvas.canvasView.framesArea);

            this.renderDeviceDecoration();
            this.initButtons();

            this.listenTo(mediator, 'grapesjs:theme:change', this.initButtons.bind(this));
            this.listenTo(mediator, 'layout:reposition', this.adjustCurrentDeviceWidth.bind(this));
            this.listenTo(this.builder, 'change:device', this.updateSelectedElement.bind(this));

            Commands.add('setDevice', {
                updateCalcPreviewDeviceWidth: this.updateCalcPreviewDeviceWidth.bind(this),

                run(editor, sender) {
                    const {Devices} = editor;
                    const device = Devices.get(sender.id);

                    this.updateCalcPreviewDeviceWidth(device);

                    editor.setDevice(sender.id);
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.add(sender.id);
                    editor.Canvas.deviceDecorator.classList.add(sender.id);
                },

                stop(editor, sender) {
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.remove(sender.id);
                    editor.Canvas.deviceDecorator.classList.remove(sender.id);
                }
            });
        },

        /**
         * Set deferred initialize
         *
         * @private
         */
        _deferredInit() {
            this.deferredInitPromise = new Promise((resolve, reject) => {
                this._deferredInitResolver = resolve;
                this._deferredInitRejecter = reject;
            });
        },

        /**
         * Resolve deferred initialize promise
         *
         * @private
         */
        _resolveDeferredInit() {
            if (this._deferredInitResolver) {
                this._deferredInitResolver(this);
            }
        },

        /**
         * Reject deferred initialize promise
         *
         * @param {object} error
         * @private
         */
        _rejectDeferredInit(error) {
            if (this._deferredInitRejecter) {
                if (error) {
                    this._deferredInitRejecter(error);
                } else {
                    this._deferredInitRejecter();
                }
            }
        },

        /**
         * Update preview device width according canvas width
         *
         * @param {Model} deviceModel
         */
        updateCalcPreviewDeviceWidth(deviceModel) {
            const {Canvas} = this.builder;
            const canvasEl = Canvas.getElement();
            const width = parseInt(deviceModel.get('width')) || 0;

            if (width >= canvasEl.offsetWidth - 100) {
                deviceModel.set('width', (canvasEl.offsetWidth - 100) + 'px');
            } else {
                deviceModel.set('width', deviceModel.get('widthMedia'));
            }
        },

        renderDeviceDecoration() {
            const {Canvas} = this.builder;
            const {canvasView} = Canvas;

            const deviceDecorator = document.createElement('div');
            deviceDecorator.classList.add('gjs-canvas-device-decorator');

            canvasView.$el.before(deviceDecorator);

            Canvas.deviceDecorator = deviceDecorator;
            this.deviceDecorator = deviceDecorator;
        },

        adjustCurrentDeviceWidth() {
            const {Devices} = this.builder;
            const currentDevice = Devices.getSelected();

            currentDevice && this.updateCalcPreviewDeviceWidth(currentDevice);
        },

        initButtons() {
            this.getBreakpoints()
                .then(() => this.createButtons())
                .catch(error => this._rejectDeferredInit(error));
        },

        /**
         * Fetch breakpoints from theme stylesheet
         * @private
         */
        _getCSSBreakpoint(allowBreakpoints = this.allowBreakpoints) {
            if (this.disposed) {
                return;
            }

            const contentDocument = this.$builderIframe[0].contentDocument;

            // If the iframe and the iframe's parent document are Same Origin, returns a Document else returns null.
            if (contentDocument === null) {
                return;
            }

            const breakpoints = viewportManager.getBreakpoints(contentDocument.documentElement, breakpoints => {
                if (Object.values(breakpoints).length === 1 && Object.values(breakpoints)[0] === 'all') {
                    return {...breakpoints, ...getLegacyBreakpoints(contentDocument.head)};
                }

                return breakpoints;
            });

            this.breakpoints = this._collectCSSBreakpoints(breakpoints)
                .filter(({name}) => !allowBreakpoints.length || allowBreakpoints.includes(name))
                .map((breakpoint, index) => {
                    breakpoint = {...breakpoint};

                    const width = breakpoint.max ? breakpoint.max + 'px' : '';

                    breakpoint['widthDevice'] = width;

                    if (breakpoint.name.includes('landscape')) {
                        breakpoint['height'] = this.calculateDeviceHeight(width, true);
                        breakpoint['widthMedia'] = width;
                    } else {
                        breakpoint['height'] = this.calculateDeviceHeight(width);
                    }

                    breakpoint.isActiveByDefault = index === 0;

                    return breakpoint;
                });

            return this.breakpoints;
        },

        /**
         * Collect and resolve CSS variables by breakpoint prefix
         * @param cssVariables
         * @returns {*}
         * @private
         * See [documentation](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/css-variables.md)
         */
        _collectCSSBreakpoints(cssVariables) {
            const regexpMax = /(max-width:\s?)([(\d+)]*)/g;
            const regexpMin = /(min-width:\s?)([(\d+)]*)/g;

            return _.reduce(cssVariables, function(collection, cssVar, varName) {
                let _result;

                const matchMax = cssVar.match(regexpMax);
                const matchMin = cssVar.match(regexpMin);

                if (matchMax || matchMin) {
                    _result = {
                        name: varName
                    };

                    matchMax ? _result['max'] = parseInt(matchMax[0].replace('max-width:', '')) : null;
                    matchMin ? _result['min'] = parseInt(matchMin[0].replace('min-width:', '')) : null;

                    collection.push(_result);
                }

                return collection;
            }, [], this);
        },

        collectBreakpoints() {
            const contentDocument = this.$builderIframe[0].contentDocument;

            // If the iframe and the iframe's parent document are Same Origin, returns a Document else returns null.
            if (contentDocument === null) {
                return;
            }

            const breakpoints = viewportManager.getBreakpoints(contentDocument.documentElement);

            return this._collectCSSBreakpoints(breakpoints);
        },

        getBreakpoints() {
            let times = 0;
            const defer = $.Deferred();
            this._intervalId = setInterval(() => {
                this._getCSSBreakpoint();

                if (this.breakpoints.length || !this.allowBreakpoints.length) {
                    clearInterval(this._intervalId);
                    defer.resolve();
                }

                if (times === this.MAX_STYLE_REQUEST_TRIES) {
                    clearInterval(this._intervalId);
                    defer.reject(`'getBreakpoints' timeout has expired, without any results`);
                }

                times++;
            }, 50);

            return defer.promise();
        },

        /**
        * Create buttons controls via breakpoints
        */
        createButtons() {
            const {Panels, Devices, Canvas} = this.builder;
            const buttons = [];

            this.breakpoints.forEach(breakpoint => {
                const device = Devices.add({
                    id: breakpoint.name,
                    width: breakpoint.widthDevice,
                    widthMedia: breakpoint.max ? breakpoint.max + 'px' : '',
                    height: breakpoint.height
                });

                buttons.push({
                    id: breakpoint.name,
                    command: 'setDevice',
                    togglable: false,
                    className: breakpoint.name,
                    active: breakpoint.isActiveByDefault,
                    attributes: {
                        'data-toggle': 'tooltip',
                        'title': this.concatTitle(breakpoint)
                    }
                });

                if (breakpoint.isActiveByDefault) {
                    Canvas.getElement().classList.add(device.id);
                    Canvas.deviceDecorator.classList.add(device.id);
                }
            });

            let panel = Panels.getPanel('devices-c');

            if (!panel) {
                panel = Panels.addPanel({
                    id: 'devices-c',
                    visible: true,
                    buttons
                });
            } else {
                panel.buttons.reset(buttons);
            }

            $(panel.view.$el.find('[data-toggle="tooltip"]')).tooltip();

            this._resolveDeferredInit();
        },

        /**
         * Calculate device height
         * @param width
         * @param invert
         * @returns {string}
         */
        calculateDeviceHeight(width, invert = false) {
            if (!width) {
                return '';
            }

            width = parseInt(width);

            const ratio = width <= 640 ? 1.7 : 1.3;
            const height = invert ? width / ratio : width * ratio;

            return Math.round(height) + 'px';
        },

        /**
         * Concat title device
         * @param breakpoint
         * @returns {string}
         */
        concatTitle(breakpoint) {
            let str = __(`oro.cms.wysiwyg.device_manager.devices.${breakpoint.name.replace(/-/g, '_')}`);

            if (breakpoint.max) {
                str += ': ' + breakpoint.max;
            }

            if (breakpoint.height) {
                str += 'x' + breakpoint.height;
            }

            return str;
        },

        updateSelectedElement(editor, deviceName) {
            const {Devices} = this.builder;
            const currentDevice = Devices.getSelected();

            this.deviceDecorator.style.setProperty('--device-width', currentDevice.get('width'));
            this.deviceDecorator.style.setProperty('--device-height', currentDevice.get('height'));

            const iframe = this.$builderIframe[0];
            const iframeWrapper = this.$framesArea.find('.gjs-frame-wrapper');
            const editorConf = this.builder.getConfig();

            editorConf.el.style.height = editorConf.height;

            $(this.canvasEl).css({
                height: ''
            });

            iframeWrapper.one('transitionend.' + this.cid, () => {
                if (iframeWrapper[0].offsetHeight >= (parseInt(editorConf.height) - this.canvasEl.offsetTop)) {
                    const styleEditor = getComputedStyle(editorConf.el);
                    const styleDevice = getComputedStyle(this.deviceDecorator);
                    const height = [
                        this.deviceDecorator.offsetHeight,
                        styleDevice['padding-top'],
                        styleDevice['padding-bottom'],
                        styleDevice['margin-top'],
                        styleDevice['margin-bottom'],
                        styleDevice['top'],
                        styleEditor['padding-top'],
                        styleEditor['padding-bottom']
                    ].reduce((a, b) => a + parseInt(b), 0);

                    editorConf.el.style.height = height + 'px';
                } else {
                    editorConf.el.style.height = editorConf.height;
                }

                $(this.canvasEl).find('#gjs-cv-tools').css({
                    // width: iframe.clientWidth,
                    height: iframe.clientHeight
                });
                $(this.canvasEl).css({
                    height: iframe.clientHeight
                });

                this.builder.trigger('canvas:refresh');
            });
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            clearInterval(this._intervalId);

            this.deviceDecorator.remove();

            this.$builderIframe.off(`.${this.cid}`);
            this.$framesArea.off(`.${this.cid}`);

            delete this.builder;
            delete this.breakpoints;
            delete this.canvasEl;
            delete this.$builderIframe;

            DevicesModule.__super__.dispose.call(this);
        }
    });

    return DevicesModule;
});
