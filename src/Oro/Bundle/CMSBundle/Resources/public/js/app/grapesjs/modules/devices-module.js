define(function(require) {
    'use strict';

    const BaseClass = require('oroui/js/base-class');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const viewportManager = require('oroui/js/viewport-manager').default;
    const __ = require('orotranslation/js/translator');

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

        allowBreakpoints: [
            'desktop',
            'tablet',
            'tablet-small',
            'mobile-big',
            'mobile-landscape',
            'mobile'
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

            const {Commands, Canvas} = this.builder;

            this.patchDeviceModel();

            this.canvasEl = Canvas.getElement();
            this.$builderIframe = $(Canvas.getFrameEl());

            this.initButtons();

            this.listenTo(mediator, 'grapesjs:theme:change', this.initButtons.bind(this));
            this.listenTo(mediator, 'layout:reposition', this.adjustCurrentDeviceWidth.bind(this));
            this.listenTo(this.builder, 'change:device', this.updateSelectedElement.bind(this));

            Commands.add('setDevice', {
                run(editor, sender) {
                    const {Devices} = editor;
                    const device = Devices.get(sender.id);

                    device.updateCalcPreviewDeviceWidth();

                    editor.setDevice(sender.id);
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.add(sender.id);
                },
                stop(editor, sender) {
                    const canvas = editor.Canvas.getElement();

                    canvas.classList.remove(sender.id);
                }
            });
        },

        patchDeviceModel() {
            const {Devices} = this.builder;

            Devices.Devices.prototype.model = Devices.Device.extend({
                editor: this.builder,

                updateCalcPreviewDeviceWidth() {
                    const {Canvas} = this.editor;
                    const canvasEl = Canvas.getElement();
                    const width = parseInt(this.get('width')) || 0;

                    if (width >= canvasEl.offsetWidth - 100) {
                        this.set('width', (canvasEl.offsetWidth - 100) + 'px');
                    } else {
                        this.set('width', this.get('widthMedia'));
                    }
                }
            });
        },

        adjustCurrentDeviceWidth() {
            const {Devices} = this.builder;
            const currentDevice = Devices.getSelected();

            currentDevice && currentDevice.updateCalcPreviewDeviceWidth();
        },

        initButtons() {
            this.getBreakpoints()
                .then(() => this.createButtons());
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

            const breakpoints = viewportManager.getBreakpoints(contentDocument.documentElement);

            this.breakpoints = this._collectCSSBreakpoints(breakpoints)
                .filter(({name}) => !allowBreakpoints.length || allowBreakpoints.includes(name))
                .map(breakpoint => {
                    breakpoint = {...breakpoint};

                    const width = breakpoint.max ? breakpoint.max + 'px' : '';

                    breakpoint['widthDevice'] = width;

                    if (breakpoint.name.includes('landscape')) {
                        breakpoint['height'] = this.calculateDeviceHeight(width, true);
                        breakpoint['widthMedia'] = width;
                    } else {
                        breakpoint['height'] = this.calculateDeviceHeight(width);
                    }

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
            const defer = $.Deferred();
            this._intervalId = setInterval(() => {
                this._getCSSBreakpoint();

                if (this.breakpoints.length || !this.allowBreakpoints.length) {
                    clearInterval(this._intervalId);
                    defer.resolve();
                }
            }, 50);

            return defer.promise();
        },

        /**
        * Create buttons controls via breakpoints
        */
        createButtons() {
            const {Panels, Devices} = this.builder;
            const buttons = [];

            this.breakpoints.forEach(breakpoint => {
                Devices.add({
                    id: breakpoint.name,
                    width: breakpoint.widthDevice,
                    widthMedia: breakpoint.max ? breakpoint.max + 'px' : ''
                });

                buttons.push({
                    id: breakpoint.name,
                    command: 'setDevice',
                    togglable: false,
                    className: breakpoint.name,
                    attributes: {
                        'data-toggle': 'tooltip',
                        'title': this.concatTitle(breakpoint)
                    }
                });
            });

            const panel = Panels.addPanel({
                id: 'devices-c',
                visible: true,
                buttons
            });

            const button = Panels.getButton('devices-c', 'desktop');
            button.set('active', true);

            $(panel.view.$el.find('[data-toggle="tooltip"]')).tooltip();
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

        updateSelectedElement(model, deviceName) {
            const selected = this.builder.getSelected();
            const iframe = this.$builderIframe[0];
            const deviceManager = this.builder.DeviceManager;
            const device = deviceManager.get(deviceName);
            const editorConf = this.builder.getConfig();

            editorConf.el.style.height = editorConf.height;

            this.$builderIframe.one('transitionend.' + this.cid, () => {
                if (iframe.offsetHeight >= (parseInt(editorConf.height) - this.canvasEl.offsetTop)) {
                    const styleEditor = getComputedStyle(editorConf.el);
                    const styleCanvas = getComputedStyle(this.canvasEl);
                    const height = [iframe.offsetHeight, this.canvasEl.offsetTop, styleEditor['padding-top'],
                        styleEditor['padding-bottom'], styleCanvas['padding-top'], styleCanvas['padding-bottom']]
                        .reduce((a, b) => a + parseInt(b), 0);

                    editorConf.el.style.height = height + 'px';
                } else {
                    editorConf.el.style.height = editorConf.height;
                }

                const leftOffset = parseInt($(iframe).css('margin-left')) +
                    parseInt($(iframe).css('border-left-width'));

                $(this.canvasEl).find('#gjs-cv-tools').css({
                    width: device.get('width'),
                    height: device.get('height'),
                    marginLeft: leftOffset
                });

                $(this.canvasEl).find('#gjs-tools').css({
                    marginLeft: -leftOffset
                });

                if (selected) {
                    this.builder.selectRemove(selected);
                    this.builder.selectAdd(selected);
                }
            });
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            clearInterval(this._intervalId);

            this.$builderIframe.off('.' + this.cid);

            delete this.builder;
            delete this.breakpoints;
            delete this.canvasEl;
            delete this.$builderIframe;

            DevicesModule.__super__.dispose.call(this);
        }
    });

    return DevicesModule;
});
