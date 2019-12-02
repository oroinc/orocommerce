define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const LinkButtonComponent = BaseComponent.extend({

        editor: null,

        constructor: function LinkButtonComponent(options) {
            LinkButtonComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.editor = options;
            const ComponentId = 'link-button';
            const domComps = options.DomComponents;
            const parentType = domComps.getType('link');
            const parentModel = parentType.model;
            const parentView = parentType.view;

            domComps.addType(ComponentId, {
                model: parentModel.extend({
                    defaults: _.extend({}, parentModel.prototype.defaults, {
                        classes: ['btn', 'btn--info'],
                        content: 'Link Button'
                    }),
                    constructor: function LinkButtonComponentModel(...args) {
                        parentModel.prototype.constructor.apply(this, args);
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';
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
            if (this.editor.ComponentRestriction.isAllow([
                'a'
            ])) {
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
        }
    });

    return LinkButtonComponent;
});
