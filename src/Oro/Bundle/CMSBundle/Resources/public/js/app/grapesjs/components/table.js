define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Create table component type for builder
     */
    const TableComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function TableComponent(options) {
            TableComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            const ComponentId = 'table';
            const domComps = options.DomComponents;
            const dType = domComps.getType('default');
            const dModel = dType.model;
            const dView = dType.view;

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'table',
                        draggable: ['div'],
                        droppable: ['tbody', 'thead', 'tfoot'],
                        classes: ['table']
                    }),
                    constructor: function TableComponentModel(...args) {
                        dModel.prototype.constructor.apply(this, args);
                    },
                    initialize: function(o, opt) {
                        dModel.prototype.initialize.call(this, o, opt);
                        const components = this.get('components');
                        if (!components.length) {
                            components.add({
                                type: 'thead'
                            });
                            components.add({
                                type: 'tbody'
                            });
                            components.add({
                                type: 'tfoot'
                            });
                        }
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';

                        if (el.tagName === 'TABLE') {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: dView
            });
        }
    });

    return TableComponent;
});
