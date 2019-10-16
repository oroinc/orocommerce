define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var error = require('oroui/js/error');

    /**
     * Static get all tags in element node
     * @param element
     * @returns {Array}
     */
    function getTags(element) {
        var _res = [];

        _res.push(element.nodeName.toLowerCase());

        for (var i = 0; i < element.childNodes.length; i++) {
            var child = element.childNodes[i];

            if (child.nodeType === 1) {
                _res.push(child.nodeName.toLowerCase());

                if (child.childNodes) {
                    _res = _res.concat(getTags(child));
                }
            }
        }

        return _res;
    }

    /**
     * Create component restriction instance
     * @param editor
     * @param options
     * @constructor
     */
    var ComponentRestriction = function(editor, options) {
        this.editor = editor;
        this.allowTags = this._prepearAllowTagsCollection(options.allowTags);

        this.resolveRestriction();
    };

    ComponentRestriction.prototype = {
        /**
         * Check existing component types
         */
        resolveRestriction: function() {
            var DomComponents = this.editor.DomComponents;
            var BlockManager = this.editor.BlockManager;
            var componentTypes = DomComponents.componentTypes;

            DomComponents.componentTypes = _.reject(componentTypes, function(type) {
                return !this.isAllowedTag(type.model.prototype.defaults.tagName);
            }, this);

            var types = _.pluck(DomComponents.componentTypes, 'id');
            var _res = [];

            _.each(BlockManager.getAll().models, function(model) {
                if (!model) {
                    return;
                }
                var content = model.get('content');

                if (_.isObject(content)) {
                    if (!_.contains(types, content.type)) {
                        _res.push(model.id);
                    }
                } else {
                    var res = _.every(this.getTags($(content).get(0)), function(tag) {
                        return this.isAllowedTag(tag);
                    }, this);

                    if (!res) {
                        _res.push(model.id);
                    }
                }
            }, this);

            _.each(_res, BlockManager.remove);
        },

        /**
         * Get element tags
         * @param element
         * @returns {Array}
         */
        getTags: function(element) {
            return getTags(element);
        },

        /**
         * Check is tag allowed
         * @param type
         * @returns {*}
         */
        isAllowedTag: function(type) {
            return _.contains(this.allowTags, type.toLowerCase());
        },

        /**
         * Is allow tags collection
         * @param tags
         * @returns {*}
         */
        isAllow: function(tags) {
            if (_.isString(tags)) {
                return this.isAllowedTag(tags);
            }

            if (_.isArray(tags)) {
                return _.every(tags, this.isAllowedTag, this);
            }
        },

        /**
         * Check HTML template
         * @param template
         */
        checkTemplate: function(template) {
            return _.every(this.getTags($(template).get(0)), function(tag) {
                var isAllowed = this.isAllowedTag(tag);
                if (!isAllowed) {
                    error.showErrorInConsole('Tag "' + tag + '" is not allowed');
                }
                return isAllowed;
            }, this);
        },

        /**
         * Resolve backend yaml config
         * @param tags
         * @returns {*}
         * @private
         */
        _prepearAllowTagsCollection: function(tags) {
            return _.map(tags, function(tag) {
                return tag.replace(/\[([^)]+)\]/, '');
            });
        }
    };

    return ComponentRestriction;
});
