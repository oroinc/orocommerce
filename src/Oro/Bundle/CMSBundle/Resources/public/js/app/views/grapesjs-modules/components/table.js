define(function(require) {
    'use strict';

    var TableComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Create table component type for builder
     */
    TableComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function TableComponent() {
            TableComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var ComponentId = 'table';
            var domComps = options.DomComponents;
            var dType = domComps.getType('default');
            var dModel = dType.model;
            var dView = dType.view;

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'table',
                        draggable: ['div'],
                        droppable: ['tbody', 'thead', 'tfoot'],
                        classes: ['table']
                    }),
                    constructor: function() {
                        dModel.prototype.constructor.apply(this, arguments);
                    },
                    initialize: function(o, opt) {
                        dModel.prototype.initialize.apply(this, arguments);
                        var components = this.get('components');
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
                        var result = '';

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
