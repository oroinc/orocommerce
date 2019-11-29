define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const error = require('oroui/js/error');

    /**
     * Static get all tags in element node
     * @param element
     * @returns {Array}
     */
    function getTags(element) {
        let _res = [];

        _res.push(element.nodeName.toLowerCase());

        for (let i = 0; i < element.childNodes.length; i++) {
            const child = element.childNodes[i];

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
    const ComponentRestriction = function(editor, options) {
        this.editor = editor;
        this.allowTags = this._prepearAllowTagsCollection(options.allowTags);

        if (options.allowTags !== false) {
            this.resolveRestriction();
        }
    };

    ComponentRestriction.prototype = {
        /**
         * Check existing component types
         */
        resolveRestriction: function() {
            const DomComponents = this.editor.DomComponents;
            const BlockManager = this.editor.BlockManager;
            const componentTypes = DomComponents.componentTypes;

            DomComponents.componentTypes = _.reject(componentTypes, function(type) {
                return !this.isAllowedTag(type.model.prototype.defaults.tagName);
            }, this);

            const types = _.pluck(DomComponents.componentTypes, 'id');
            const _res = [];

            _.each(BlockManager.getAll().models, function(model) {
                if (!model) {
                    return;
                }
                const content = model.get('content');

                if (_.isObject(content)) {
                    if (!_.contains(types, content.type)) {
                        _res.push(model.id);
                    }
                } else {
                    const res = _.every(this.getTags($(content).get(0)), function(tag) {
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
            return _.uniq(getTags(element));
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
                const isAllowed = this.isAllowedTag(tag);
                if (!isAllowed) {
                    error.showErrorInConsole('Tag "' + tag + '" is not allowed');
                }
                return isAllowed;
            }, this);
        },

        validate: function(template) {
            const restricted = [];

            try {
                _.each(this.getTags($(template).get(0)), function(tag) {
                    if (!this.isAllowedTag(tag)) {
                        restricted.push(_.capitalize(tag));
                    }
                }, this);
            } catch (e) {
                return restricted;
            }

            return _.uniq(restricted);
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
