define(function(require) {
    'use strict';

    var ConsentEntityTreeSelectFormView;
    var _ = require('underscore');
    var EntityTreeSelectFormTypeView = require('oroform/js/app/components/entity-tree-select-form-type-view');

    /**
     * Extension for jsTree view from @entity-tree-select-form-type-component
     * Add new way for update and re-render tree from response data
     */
    ConsentEntityTreeSelectFormView = EntityTreeSelectFormTypeView.extend({
        optionNames: EntityTreeSelectFormTypeView.prototype.optionNames.concat([
            'updateApiAccessor', 'chooseWebCatalogMessage', 'loadingMask'
        ]),

        /**
         * @property {String}
         */
        chooseWebCatalogMessage: _.__('oro.consent.jstree.please_choose_web_catalog'),

        /**
         * @property {View}
         */
        loadingMask: null,

        /**
         * @constructor
         */
        constructor: function ConsentEntityTreeSelectFormView() {
            ConsentEntityTreeSelectFormView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            ConsentEntityTreeSelectFormView.__super__.initialize.apply(this, arguments);
            this.$fieldSelector.on('update:field', _.bind(this._onUpdateFieldValue, this));
        },

        /**
         * Update data from the server when field get update event
         *
         * @param {jQuery.Event} event
         * @returns {*}
         */
        _onUpdateFieldValue: function(event) {
            if (_.isEmpty(event.updatedData.id)) {
                this.onDeselect();
                this.showSearchResultMessage(this.chooseWebCatalogMessage);
                this.disableSearchField(true);
                return;
            }

            console.log(event.updatedData);
            this.updateTree({
                entity: 'webcatalogs',
                id: event.updatedData.id
            });

            this.isEmptyTreeMessage = _.__('oro.consent.jstree.web_catlog_is_empty',
                {webCatalog: event.updatedData.name}
            );
        }
    });

    return ConsentEntityTreeSelectFormView;
});
