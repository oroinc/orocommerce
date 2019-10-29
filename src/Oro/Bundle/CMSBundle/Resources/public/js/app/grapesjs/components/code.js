define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const CodeComponent = BaseComponent.extend({

        editor: null,

        constructor: function CodeComponent(options) {
            CodeComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.editor = options;
            const ComponentId = 'code';
            const domComps = options.DomComponents;
            const parentType = domComps.getType('text');
            const parentModel = parentType.model;
            const parentView = parentType.view;

            domComps.addType(ComponentId, {
                model: parentModel.extend({
                    defaults: _.extend({}, parentModel.prototype.defaults, {
                        tagName: 'code',
                        content: 'Type code here'
                    }),
                    constructor: function CodeComponentModel(...args) {
                        parentModel.prototype.constructor.apply(this, args);
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';
                        if (el.tagName === 'CODE') {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: parentView
            });

            this.createComponentButton();
        },

        createComponentButton: function() {
            this.editor.BlockManager.add('code', {
                id: 'code',
                label: _.__('oro.cms.wysiwyg.component.code'),
                category: 'Basic',
                attributes: {
                    'class': 'fa fa-code'
                },
                content: {
                    type: 'code'
                }
            });
        }
    });

    return CodeComponent;
});
