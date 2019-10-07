define(function(require) {
    'use strict';

    var TableResponsiveComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Create responsive table component type for builder
     */
    TableResponsiveComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function TableResponsiveComponent() {
            TableResponsiveComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var ComponentId = 'table-responsive';
            var domComps = options.DomComponents;
            var dType = domComps.getType('default');
            var dModel = dType.model;
            var dView = dType.view;

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'div',
                        draggable: ['div'],
                        droppable: ['table', 'tbody', 'thead', 'tfoot'],
                        classes: [ComponentId]
                    }),
                    constructor: function() {
                        dModel.prototype.constructor.apply(this, arguments);
                    },
                    initialize: function(o, opt) {
                        dModel.prototype.initialize.apply(this, arguments);
                        var components = this.get('components');
                        if (!components.length) {
                            components.add({
                                type: 'table'
                            });
                        }
                    }
                }, {
                    isComponent: function(el) {
                        var result = '';
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

            TableResponsiveComponent.__super__.initialize.apply(this, arguments);
        }
    });

    return TableResponsiveComponent;
});
