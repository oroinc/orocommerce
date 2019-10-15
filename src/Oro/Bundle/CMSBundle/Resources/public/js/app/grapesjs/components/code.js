define(function(require) {
    'use strict';

    var CodeComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    CodeComponent = BaseComponent.extend({

        editor: null,

        constructor: function CodeComponent() {
            CodeComponent.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.editor = options;
            var ComponentId = 'code';
            var domComps = options.DomComponents;
            var parentType = domComps.getType('text');
            var parentModel = parentType.model;
            var parentView = parentType.view;

            domComps.addType(ComponentId, {
                model: parentModel.extend({
                    defaults: _.extend({}, parentModel.prototype.defaults, {
                        tagName: 'code',
                        content: 'Type code here'
                    }),
                    constructor: function CodeComponentModel() {
                        parentModel.prototype.constructor.apply(this, arguments);
                    }
                }, {
                    isComponent: function(el) {
                        var result = '';
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
