define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const _ = require('underscore');

    const VariantBodyView = BaseView.extend({
        options: {},
        selectors: {
            container: null,
            trigger: null,
            body: null
        },
        expanded: false,
        loadingMaskView: null,
        $trigger: null,
        $container: null,

        /**
         * @inheritDoc
         */
        constructor: function VariantBodyView(options) {
            VariantBodyView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$trigger = this.options.el.closest('.content-variant-item').find(this.options.selectors.trigger);
            this.$container = this.options.el.closest('.content-variant-item').find(this.options.selectors.container);
            this.loadingMaskView = new LoadingMaskView({container: this.$container});

            this.initializeCollapsedState();
            this.$trigger.on('click', _.bind(this.onToggle, this));
        },

        onToggle: function(e) {
            if (this.$container.find(this.options.selectors.body).length !== 0) {
                return;
            }

            if (this.$container.hasClass('show')) {
                return;
            }

            this.loadingMaskView.show();
            this.loadBody();
        },

        initializeCollapsedState: function() {
            if (this.options.expanded) {
                this.loadingMaskView.show();
                this.$container.addClass('show');
                this.loadBody();
            } else {
                this.$trigger.addClass('collapsed');
            }
        },

        loadBody: function() {
            const bodyPrototype = this.$el.data('body-prototype');
            if (bodyPrototype) {
                this.$container
                    .append(bodyPrototype)
                    .trigger('content:changed');

                this.validate();
            }
            this.loadingMaskView.hide();
        },

        validate: function() {
            const $form = this.$el.closest('form');
            if ($form.data('validator')) {
                $form.validate();
            }
        }
    });

    return VariantBodyView;
});
