define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const TextBasicComponent = BaseComponent.extend({
        constructor: function TextBasicComponent(options) {
            TextBasicComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.editor = options;

            this.editor.BlockManager.get('text-basic').set({
                label: _.__('oro.cms.wysiwyg.component.text_basic')
            });
        }
    });

    return TextBasicComponent;
});
