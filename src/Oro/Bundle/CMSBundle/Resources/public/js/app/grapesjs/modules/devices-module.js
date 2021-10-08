define(function(require) {
    'use strict';

    const BaseClass = require('oroui/js/base-class');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const viewportManager = require('oroui/js/viewport-manager');
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
        initialize: function(options) {
            if (!options.builder) {
                throw new Error('Required option builder not found.');
            } else {
                this.builder = options.builder;
            }

            if (options.allowBreakpoints.length) {
                this.allowBreakpoints = options.allowBreakpoints;
            }

            this.canvasEl = this.builder.Canvas.getElement();
            this.$builderIframe = $(this.builder.Canvas.getFrameEl());

            this.initButtons();

            this.listenTo(mediator, 'grapesjs:theme:change', this.initButtons.bind(this));
            this.listenTo(this.builder, 'change:device', this.updateSelectedElement.bind(this));
        },

        initButtons() {
            this.getBreakpoints()
                .then(() => this.createButtons());
        },

        /**
         * Fetch breakpoints from theme stylesheet
         * @private
         */
        _getCSSBreakpoint() {
            if (this.disposed) {
                return;
            }

            const contentDocument = this.$builderIframe[0].contentDocument;

            // If the iframe and the iframe's parent document are Same Origin, returns a Document else returns null.
            if (contentDocument === null) {
                return;
            }

            const breakpoints = mediator.execute('fetch:head:computedVars', contentDocument.head);

            this.breakpoints = viewportManager._collectCSSBreakpoints(breakpoints)
                .filter(({name}) => this.allowBreakpoints.includes(name))
                .map(breakpoint => {
                    breakpoint = {...breakpoint};

                    const width = this.calculateDeviceWidth( breakpoint.max ? breakpoint.max + 'px' : false);

                    breakpoint['widthDevice'] = width;

                    if (breakpoint.name.includes('landscape')) {
                        breakpoint['height'] = this.calculateDeviceHeight(width, true);
                        breakpoint['widthMedia'] = width;
                    } else {
                        breakpoint['height'] = this.calculateDeviceHeight(width);
                    }

                    return breakpoint;
                });
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
            if (!this.builder) {
                return;
            }
            const devicePanel = this.builder.Panels.getPanel('devices-c');
            const deviceButton = devicePanel.get('buttons');
            const DeviceManager = this.builder.DeviceManager;
            const Commands = this.builder.Commands;
            const activeBtn = deviceButton.where({active: true});
            let activeBtnId = 'desktop';

            if (activeBtn.length) {
                const breakpoint = this.breakpoints.find(el => el.name === activeBtn[0].attributes.id);

                if (breakpoint !== void 0) {
                    activeBtnId = breakpoint.name;
                }
            }

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

                DeviceManager.add(breakpoint.name, breakpoint.widthDevice, breakpoint);

                deviceButton.add({
                    id: breakpoint.name,
                    command: 'setDevice',
                    togglable: false,
                    className: breakpoint.name,
                    active: breakpoint.name === activeBtnId,
                    attributes: {
                        'data-toggle': 'tooltip',
                        'title': this.concatTitle(breakpoint)
                    }
                });

                $(devicePanel.view.$el.find('[data-toggle="tooltip"]')).tooltip();
            }, this);

            devicePanel.view.$el.addClass('init');
            this.builder.CssComposer.render();
        },

        /**
         * Calculate device height
         * @param width
         * @param invert
         * @returns {string}
         */
        calculateDeviceHeight(width, invert) {
            if (!width) {
                return '';
            }

            width = parseInt(width);

            if (!invert) {
                invert = false;
            }
            const ratio = width <= 640 ? 1.7 : 1.3;
            const height = invert ? width / ratio : width * ratio;

            return Math.round(height) + 'px';
        },

        calculateDeviceWidth(width) {
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
         * @returns {string}
         */
        concatTitle(breakpoint) {
            let str = __(`oro.cms.wysiwyg.device_manager.devices.${breakpoint.name.replace('-', '_')}`);

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
