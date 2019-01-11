define(function(require) {
    'use strict';

    var ConsentUpdateContentNodesView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * Unification and networking between fields
     */
    ConsentUpdateContentNodesView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'listenedFieldName', 'triggeredFieldName',
            'listenedElement', 'triggeredElement'
        ]),

        /**
         * @property {String}
         */
        listenedFieldName: null,

        /**
         * @property {String}
         */
        triggeredFieldName: null,

        /**
         * @property {jQuery}
         */
        listenedElement: null,

        /**
         * @property {jQuery}
         */
        triggeredElement: null,

        /**
         * @constructor
         */
        constructor: function ConsentUpdateContentNodesView() {
            ConsentUpdateContentNodesView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize
         *
         * @param options
         */
        initialize: function(options) {
            ConsentUpdateContentNodesView.__super__.initialize.apply(this, arguments);
            this._initElements();
            this._bindEvents();
            this._triggerUpdateAction();
        },

        /**
         * Find and save active field elements
         *
         * @private
         */
        _initElements: function() {
            this.listenedElement = this.$('[name="' + this.listenedFieldName + '"]');
            this.triggeredElement = this.$('[name="' + this.triggeredFieldName + '"]');
        },

        /**
         * Create listener
         *
         * @private
         */
        _bindEvents: function() {
            this.$('[name="' + this.listenedFieldName + '"]').on('change', _.bind(this._triggerUpdateAction, this));
        },

        /**
         * Create update event for field which should update
         *
         * @private
         */
        _triggerUpdateAction: function() {
            var select2Instance = this.listenedElement.select2('data');
            this.triggeredElement.trigger({
                type: 'update:field',
                updatedData: {
                    id: this.listenedElement.val(),
                    name: select2Instance ? select2Instance.name : null
                }
            });
        }
    });

    return ConsentUpdateContentNodesView;
});
