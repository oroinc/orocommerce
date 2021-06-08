define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const error = require('oroui/js/error');
    const {stripRestrictedAttrs} = require('orocms/js/app/grapesjs/plugins/grapesjs-style-isolation');

    /**
     * Static get all tags in element node
     * @param {DOM} element
     * @param {Boolean} mirror
     * @returns {Array}
     */
    function getTags(element, mirror = false) {
        let _res = [];
        _res.push(computedTagData(element, mirror));

        for (let i = 0; i < element.childNodes.length; i++) {
            const child = element.childNodes[i];

            if (child.nodeType === 1) {
                if (child.childNodes) {
                    _res = _res.concat(getTags(child, mirror));
                }
            }
        }

        return _res;
    }

    /**
     * Compose tag data
     * @param {DOM} node
     * @param {Boolean} mirror
     * @returns {*}
     */
    function computedTagData(node, mirror) {
        const attrs = [];
        _.each(node.attributes, attr => attrs.push(attr.name));
        const tagName = node.nodeName.toLowerCase();
        let tagMirror = '';
        if (mirror) {
            tagMirror = node.outerHTML.match(/^<[^>]*>/g)[0];
        }

        return attrs.length ? _.compact([tagName, attrs, tagMirror]) : tagName;
    }

    /**
     * Create component restriction instance
     * @param editor
     * @param options
     * @constructor
     */
    const ComponentRestriction = function(editor, options) {
        this.editor = editor;
        this.allowTags = options.allowTags ? this._prepareAllowTagsCollection(options.allowTags) : false;

        if (options.allowTags) {
            this.resolveRestriction();
        }

        editor.getAllowedConfig = () => {
            return this.allowTags;
        };

        this.allowedIframeDomains = options.allowedIframeDomains || null;

        editor.getAllowedIframeDomains = () => {
            return this.allowedIframeDomains;
        };
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
                    const res = _.every(this.getTags($(stripRestrictedAttrs(content)).get(0)), function(tag) {
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
        getTags: function(element, mirror = false) {
            return _.uniq(getTags(element, mirror));
        },

        /**
         * Check is domain allowed
         * @param {string} domain
         * @returns {boolean}
         */
        isAllowedDomain: function(domain) {
            const allowedIframeDomains = this.editor.getAllowedIframeDomains();
            if (allowedIframeDomains === null) {
                return true;
            }

            try {
                const {hostname, pathname} = new URL(domain);

                return allowedIframeDomains.includes(`${hostname}${pathname}`);
            } catch (e) {
                return false;
            }
        },

        /**
         * Check is tag allowed
         * @param {string|array} type
         * @returns {boolean}
         */
        isAllowedTag: function(type) {
            return this.allowTags === false || this.contains(type);
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
         * Check contains restricted tags data in purifying config
         * @param {Array|String} type
         * @returns {*}
         */
        contains(type) {
            if (_.isString(type)) {
                type = type.toLowerCase();
                return this.allowTags.find(tag => {
                    return tag[0] === type;
                }) || false;
            }

            if (_.isArray(type)) {
                const typeFlat = [type[0].toLowerCase(), type[1]].flat();
                return this.allowTags.find(tag => {
                    if (tag[0] === typeFlat[0]) {
                        return !_.difference(typeFlat, tag.flat()).length;
                    }
                }) || false;
            }
        },

        /**
         * Check HTML template
         * @param template
         */
        checkTemplate: function(template) {
            return _.every(this.getTags($('<div />').html(stripRestrictedAttrs(template)).get(0)), function(tag) {
                const isAllowed = this.isAllowedTag(tag);
                if (!isAllowed) {
                    error.showErrorInConsole('Tag "' + tag + '" is not allowed');
                }
                return isAllowed;
            }, this);
        },

        /**
         * Check and output validation results
         * @param {String} template
         * @param {Boolean} nativeOut
         * @returns {[]}
         */
        validate: function(template, nativeOut = false) {
            const restricted = [];

            try {
                _.each(this.getTags(
                    $('<div />').html(stripRestrictedAttrs(template)).get(0), nativeOut
                ), function(tag) {
                    if (!this.isAllowedTag(tag)) {
                        restricted.push(_.isArray(tag)
                            ? this.normalize(!nativeOut ? tag : tag[2])
                            : tag.toUpperCase());
                    }
                }, this);
            } catch (e) {
                return restricted;
            }

            return _.uniq(restricted);
        },

        /**
         * Normalize output data for validation messages
         * @param {Array|String} tag
         * @returns {string|*|void}
         */
        normalize(tag) {
            if (_.isArray(tag)) {
                const conf = this.getConfig(tag[0]) || [];
                const attr = conf.length ? _.difference(tag.flat(), conf.flat()) : tag[1];
                return `${tag[0].toUpperCase()} (${attr.join(', ')})`;
            } else {
                return tag.replace(/(?![\w\-]+)=""/g, '');
            }
        },

        /**
         * Find tag config from purifying config
         * @param tagName
         * @returns {*}
         */
        getConfig(tagName) {
            return this.allowTags.find(tag => tag[0] === tagName);
        },

        /**
         * Resolve backend yaml config
         * @param tags
         * @returns {*}
         * @private
         */
        _prepareAllowTagsCollection(tags) {
            tags = tags.slice();
            const attrsRegexp = /(?:\[)(.*?)(?:\])/;
            const globalRest = tags[0].match(attrsRegexp);
            const globalAttr = globalRest ? globalRest[1].split('|') : [];
            tags.shift();

            return _.map(tags, function(tag) {
                let attrs = tag.match(attrsRegexp);
                const tagName = tag.replace(/\[([^)]+)\]/, '');
                attrs = attrs ? attrs[1].split('|') : [];
                attrs = attrs.map(attr => attr[0] === '!' ? attr.substr(1) : attr);
                return [tagName, attrs.concat(globalAttr)];
            });
        }
    };

    return ComponentRestriction;
});
