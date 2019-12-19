define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');

    const DefaultComponent = BaseComponent.extend({
        constructor: function DefaultComponent(...args) {
            DefaultComponent.__super__.constructor.apply(this, args);
        },

        initialize(editor) {
            this.editor = editor;
            const ComponentId = 'default';
            const domComps = editor.DomComponents;
            const parentType = domComps.getType(ComponentId);
            const parentModel = parentType.model;
            const parentView = parentType.view;

            domComps.addType(ComponentId, {
                model: parentModel,
                view: parentView.extend({
                    constructor: function DefaultComponentView(...args) {
                        parentView.prototype.constructor.apply(this, args);
                    },
                    updateAttributes() {
                        const attrs = [];
                        const {model, $el, el, config} = this;
                        const {highlightable, textable, type} = model.attributes;
                        const {draggableComponents} = config;

                        const defaultAttr = {
                            'data-gjs-type': type || 'default',
                            ...(draggableComponents ? {draggable: true} : {}),
                            ...(highlightable ? {'data-highlightable': 1} : {}),
                            ...(textable
                                ? {
                                    'contenteditable': 'false',
                                    'data-gjs-textable': 'true'
                                }
                                : {})
                        };

                        // Remove all current attributes
                        _.each(el.attributes, attr => attrs.push(attr.nodeName));
                        attrs.forEach(attr => $el.removeAttr(attr));
                        const attr = {
                            ...defaultAttr,
                            ...model.getAttributes()
                        };

                        // Remove all `false` attributes
                        _.keys(attr).forEach(key => attr[key] === false && delete attr[key]);
                        $el.attr(attr);

                        this.updateStyle();
                    }
                })
            });
        }
    });

    return DefaultComponent;
});
