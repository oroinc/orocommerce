define(function(require) {
    'use strict';

    var ProductCollectionApplyQueryComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    /**
     * Perform synchronization between segment definition filters block and grid. By click on "apply the query" button
     * will apply the definition filters to the related grid.
     */
    ProductCollectionApplyQueryComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            segmentDefinitionSelectorTemplate: 'input[name="%s"]'
        },

        /**
         * @property {Object}
         */
        requiredOptions: ['segmentDefinitionFieldName', 'gridWidgetContainerSelector', 'controlsBlockAlias'],

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]);
            }, this));
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            this.options._sourceElement
                .on('click', _.bind(this.onApplyQuery, this));
        },

        onApplyQuery: function(e) {
            e.preventDefault();
            $(this.options.gridWidgetContainerSelector).removeClass('hide');
            mediator.trigger('grid-sidebar:change:' + this.options.controlsBlockAlias, {
                params: {segmentDefinition: this._getSegmentDefinition()}
            });
        },

        /**
         * @private
         */
        _getSegmentDefinition: function() {
            return $(
                this.options.segmentDefinitionSelectorTemplate.replace('%s', this.options.segmentDefinitionFieldName)
            ).val();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
