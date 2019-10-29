define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const tableResponsiveTemplate = require('tpl-loader!orocms/templates/grapesjs-table-responsive.html');

    /**
     * Create responsive table component type for builder
     */
    const TableResponsiveComponent = BaseComponent.extend({

        editor: null,

        /**
         * @inheritDoc
         */
        constructor: function TableResponsiveComponent(options) {
            TableResponsiveComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.editor = options;

            const ComponentId = 'table-responsive';
            const domComps = options.DomComponents;
            const dType = domComps.getType('default');
            const dModel = dType.model;
            const dView = dType.view;

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'div',
                        draggable: ['div'],
                        droppable: ['table', 'tbody', 'thead', 'tfoot'],
                        classes: [ComponentId]
                    }),
                    constructor: function TableResponsiveComponentModel(...args) {
                        dModel.prototype.constructor.apply(this, args);
                    },
                    initialize: function(o, opt) {
                        dModel.prototype.initialize.call(this, o, opt);
                        const components = this.get('components');
                        if (!components.length) {
                            components.add({
                                type: 'table'
                            });
                        }
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';
                        if (el.tagName === 'DIV' && el.className.indexOf(ComponentId) !== -1) {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: dView
            });

            this.createComponentButton();
        },

        createComponentButton: function() {
            if (this.editor.ComponentRestriction.isAllow([
                'div', 'table', 'tbody', 'thead', 'tfoot', 'tr', 'td', 'th'
            ])) {
                this.editor.BlockManager.add('responsive-table', {
                    id: 'table-responsive',
                    label: _.__('oro.cms.wysiwyg.component.table'),
                    category: 'Basic',
                    attributes: {
                        'class': 'fa fa-table'
                    },
                    content: tableResponsiveTemplate()
                });
            }
        }
    });

    return TableResponsiveComponent;
});
