define(function(require) {
    'use strict';

    var LinkButtonComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    LinkButtonComponent = BaseComponent.extend({

        editor: null,

        constructor: function LinkButtonComponent() {
            LinkButtonComponent.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.editor = options;
            var ComponentId = 'link-button';
            var domComps = options.DomComponents;
            var parentType = domComps.getType('link');
            var parentModel = parentType.model;
            var parentView = parentType.view;

            domComps.addType(ComponentId, {
                model: parentModel.extend({
                    defaults: _.extend({}, parentModel.prototype.defaults, {
                        classes: ['btn', 'btn--info'],
                        content: 'Link Button'
                    }),
                    constructor: function LinkButtonComponentModel() {
                        parentModel.prototype.constructor.apply(this, arguments);
                    }
                }, {
                    isComponent: function(el) {
                        var result = '';
                        if (el.tagName === 'A' && el.className.indexOf('btn') !== -1) {
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
            this.editor.BlockManager.add('link-button', {
                id: 'link-button',
                label: _.__('oro.cms.wysiwyg.component.link_button'),
                category: 'Basic',
                attributes: {
                    'class': 'fa fa-hand-pointer-o'
                },
                content: {
                    type: 'link-button'
                }
            });
        }
    });

    return LinkButtonComponent;
});
