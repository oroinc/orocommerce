import __ from 'orotranslation/js/translator';

export default (BaseTypeModel, {settings}) => {
    const IconTypeModel = BaseTypeModel.extend({
        settings,

        constructor: function IconTypeModel(...args) {
            return IconTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            this.set('tagName', 'svg');
            this.addClass('theme-icon');

            this.listenTo(this, 'saveSettings', ({iconId}) => {
                this.set('iconId', iconId);
                this.set('setup', true);
            });

            this.initToolbar();
        },

        getAttrToHTML() {
            const attrs = IconTypeModel.__super__.getAttrToHTML.call(this);

            if (attrs.class) {
                const classes = attrs.class.split(' ').filter(
                    cls => !this.get('privateClasses').includes(cls)
                );

                if (classes.length) {
                    attrs.class = classes.join(' ');
                } else {
                    delete attrs.class;
                }
            }

            if (attrs['data-init-icon']) {
                delete attrs['data-init-icon'];
            }

            if (attrs['draggable']) {
                delete attrs['draggable'];
            }

            return Object.keys(attrs).length ? `, ${JSON.stringify(attrs)}` : '';
        },

        toHTML() {
            return `{{ widget_icon("${this.get('iconId')}"${this.getAttrToHTML()}) }}`;
        },

        /**
         * Extend init toolbar
         * @param {boolean} reset
         */
        initToolbar({reset = false} = {}) {
            if (reset) {
                this.unset('toolbar');
            }

            IconTypeModel.__super__.initToolbar.call(this);

            if (!this.get('toolbar').find(toolbar => toolbar.id === 'component-settings')) {
                const toolbarSettings = {
                    id: 'component-settings',
                    attributes: {
                        'class': 'fa fa-gear',
                        'label': __('oro.cms.wysiwyg.dialog.component_settings.label', {name: this.get('name')})
                    },
                    command: 'component:settings-dialog'
                };

                this.set('toolbar', [
                    toolbarSettings,
                    ...this.get('toolbar')
                ]);
            }
        }
    });

    Object.defineProperty(IconTypeModel.prototype, 'defaults', {
        value: {
            ...IconTypeModel.prototype.defaults,
            iconId: 'add-note',
            name: __('oro.cms.wysiwyg.component.icon.label'),
            classes: ['theme-icon'],
            privateClasses: ['theme-icon'],
            droppable: false,
            draggable: true,
            traits: ['id', 'title', {
                type: 'icon-id',
                changeProp: true
            }],
            unstylable: [
                'float', 'display', 'label-parent-flex', 'flex-direction',
                'justify-content', 'align-items', 'flex', 'align-self', 'order'
            ],
            resizable: {
                ratioDefault: true,
                maxDim: 500,
                mousePosFetcher(event) {
                    return {
                        x: Math.round(event.clientX),
                        y: Math.round(event.clientY)
                    };
                }
            }
        }
    });

    return IconTypeModel;
};
