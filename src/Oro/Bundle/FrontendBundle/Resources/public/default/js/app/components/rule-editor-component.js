define(function(require) {
    'use strict';

    var RuleEditorComponent;
    var ViewComponent = require('oroui/js/app/components/view-component');
    var $ = require('jquery');
    var _ = require('underscore');

    RuleEditorComponent = ViewComponent.extend({
        view: 'orofrontend/default/js/app/views/rule-editor-view',

        /**
         *
         * @property {Object}
         */
        options: {
            operations: {
                math: ['+', '-', '%', '*', '/'],
                bool: ['AND', 'OR'],
                equality: ['=', '!='],
                compare: ['>', '<', '<=', '>=', 'like'],
                inclusion: ['in', 'not in']
            },
            allowedOperations: ['math', 'bool', 'equality', 'compare', 'inclusion'],
            termLevelLimit: 3
        },

        /**
         * Component initialize.
         *
         * @param options {Object}
         */
        initialize: function(options) {
            this.cases = {}; // all string cases based on options.data
            this.error = {}; // reserved for error messages
            this.opsRegEx = {}; // regexp for operations
            this.allowed = {}; // allowed operations based on current input options
            this.entities = {}; // root entities
            this.fields = {};  // fields data

            this.options = _.defaults(options || {}, this.options);
            this.options.view = this.options.view || this.view;
            this.options.component = this;

            var entities = this.options.entities;

            this.isEntities = this.options.entities &&
                !_.isEmpty(entities.fields_data) &&
                !_.isEmpty(entities.root_entities);

            if (this.isEntities) {
                this.fields = entities.fields_data;

                _.each(entities.root_entities, function(item, key) {
                    this.entities[item] = {
                        type: 'relation',
                        relation_alias: key
                    };
                }, this);
            }

            _.each(this.options.allowedOperations, function(name) {
                this.allowed[name] = true;
            }, this);

            this.cases.data = this._getStrings(this.options.data);

            _.each(this.options.allowedOperations, function(item) {
                this.cases[item] = this._getStrings(this.options.operations[item]);
                this.opsRegEx[item] = this._getRegexp(this.options.operations[item], item);
            }, this);

            return RuleEditorComponent.__super__.initialize.apply(this, arguments);
        },

        _initializeView: function(options, View) {
            RuleEditorComponent.__super__._initializeView.apply(this, arguments);
            this.initHelper(this.view.$el);
        },

        /**
         * Returns updated value and sets correct cursor position
         *
         * @param query {String}
         * @param item {String}
         * @param position {Object}
         * @param [newCaretPosition] {Number}
         * @returns {String}
         */
        setUpdatedValue: function(query, item, position, newCaretPosition) {
            var $el = this.view.$el;
            var update = this._getUpdatedData(query, item, position);

            this._setCaretPosition($el, newCaretPosition || update.position || $el[0].selectionStart);

            return update.value;
        },

        /**
         * Initialize helper/additional control
         *
         * @param $viewEl {jQuery}
         */
        initHelper: function($viewEl) {
            /**
             * Adds selected value from helper to main input and sets correct cursor position
             *
             * @param $helperEl {jQuery}
             */
            var setValue = _.bind(function($helperEl) {
                var $queryEl = this.view.$el;
                var query = $queryEl.val();
                var caretPosition = $queryEl[0].selectionStart;
                var wordPosition = this._getWordPosition(query, caretPosition);
                var termParts = this._getTermPartUnderCaret(this._getWordUnderCaret(query, wordPosition).current, caretPosition - wordPosition.start);
                var changedPart = termParts.current + '[' + $helperEl.val() + ']';

                $helperEl.hide();

                $queryEl.val(this.setUpdatedValue(query, changedPart + termParts.tail, wordPosition, wordPosition.start + changedPart.length));
            }, this);

            _.each(this.options.dataSource, function(source, key) {
                if (_.isString(source)) {
                    var $el = $(source);
                    source = {
                        $el: $el
                    };
                    $el.on('change', function(e) {
                        setValue($(e.target));
                    });
                    $el.hide().insertAfter($viewEl);

                    this.options.dataSource[key] = source;
                }
            }, this);

            $viewEl.trigger('content:changed');
        },

        /**
         * Returns boolean of string validation.
         *
         * @param value {String}
         * @returns {Boolean}
         */
        isValid: function(value) {
            if (_.isEmpty(value)) {
                return true;
            }

            if (!this._validBrackets(value)) {
                return false;
            }

            var normalized = this._getNormalized(value);
            var words = this._splitString(normalized.string, ' ');
            var groups = this._getGroups(words);

            if (!this.allowed.bool && groups.bool.length) {
                return false;
            }

            var logicIsValid = _.last(groups.bool) !== _.last(words) && _.every(groups.bool, function(item) {
                    return this._contains(this.options.operations.bool, item);
                }, this);

            var dataWordsAreValid = _.every(groups.expr, function(item) {
                return this._checkWord(item).isValid;
            }, this);

            return logicIsValid && dataWordsAreValid;
        },

        /**
         * Returns word on cursor position.
         *
         * @param string {String}
         * @param position {Number|Object}
         * @param [re] {RegExp}
         * @returns {Object}
         * @private
         */
        _getWordUnderCaret: function(string, position, re) {
            /**
             * Returns previous word
             *
             * @param _position {Object}
             * @param offset {Number}
             * @returns {*|String}
             */
            var getPrevWord = _.bind(function(_position, offset) {
                var prevPos = _position.spaces[_position.index - offset - 1];

                return !_.isUndefined(prevPos) ? this._getStringPart(string, prevPos, _position.spaces[_position.index - offset]).trim() : undefined;
            }, this);

            var wordPosition = _.isObject(position) ? position : this._getWordPosition(string, position, re);
            var currentWord = this._getStringPart(string, wordPosition.start, wordPosition.end);
            var prevWord = getPrevWord(wordPosition, 1);

            if (!prevWord) {
                prevWord = getPrevWord(wordPosition, 2);
            }

            return {
                current: currentWord ? currentWord.trim() : currentWord,
                previous: prevWord
            };
        },

        /**
         * Returns boolean of brackets validation.
         *
         * @param value {String}
         * @returns {Boolean}
         * @private
         */
        _validBrackets: function(value) {
            var breakLoop = false;
            var expected = [];
            var emptyBrackets = value.match(/(\[\])|(\(\))|(\{\})/gi);
            var bracketsOnly = value.replace(/[^\[\](){}]/g, '');

            _.each(bracketsOnly, function(char) {
                if (!breakLoop) {
                    setOpen(char, '(', ')');
                    setOpen(char, '[', ']');
                    checkClose(char, ')');
                    checkClose(char, ']');
                }
            });

            if (breakLoop || !_.isEmpty(expected) || emptyBrackets) {
                this.error.brackets = (emptyBrackets ? 'Has empty brackets ' : 'Wrong brackets balance') + ' in \'' + value + '\'';
                return false;
            }

            return true;

            /**
             * Adds expected 'close' bracket.
             *
             * @param symbol {String}
             * @param char {String}
             * @param closeExpect {String}
             */
            function setOpen(symbol, char, closeExpect) {
                if (char === symbol) {
                    expected.push(closeExpect);
                }
            }

            /**
             * Check expected 'close' bracket and break looping if not.
             *
             * @param symbol {String}
             * @param char {String}
             */
            function checkClose(symbol, char) {
                if (char === symbol) {
                    breakLoop = expected.pop() !== char;
                }
            }
        },

        /**
         * Returns value string with injected selected item in position.
         *
         * @param query {String}
         * @param item {String}
         * @param position {Object}
         * @returns {{value: String, position: Object}}
         * @private
         */
        _getUpdatedData: function(query, item, position) {
            var cutBefore = _.isNull(position.start) ? position.spaces[position.index] : position.start;
            var cutAfter = _.isNull(position.end) ? position.spaces[position.index] : position.end;
            var stringBefore = this._getStringPart(query, 0, cutBefore);
            var stringAfter = this._getStringPart(query, cutAfter);

            return {
                value: stringBefore + item + stringAfter,
                position: cutBefore + item.length
            };
        },

        /**
         * Returns position for absolute positioned element (select2)
         *
         * @param $el {jQuery}
         * @returns {{top: Number, left: Number}}
         * @private
         */
        _getPosition: function($el) {
            var pos = _.extend({}, $el.position(), {height: $el[0].offsetHeight});

            return {
                top: pos.top + pos.height + scrollOffset($el),
                left: pos.left
            };

            function scrollOffset($el) {
                var offset = 0;
                var stopProcess = false;

                $el.parents().each(function(i, el) {
                    if (el !== document.body && el !== document.html && !stopProcess) {
                        offset += el.scrollTop;
                        stopProcess = $(el).css('position') === 'relative';
                    }
                });

                return offset;
            }
        },

        /**
         * Set cursor position
         *
         * @param $el {jQuery}
         * @param position {Number}
         * @private
         */
        _setCaretPosition: function($el, position) {
            $el.focus();

            setTimeout(function() {
                $el[0].selectionStart = $el[0].selectionEnd = position;
                $el.focus();
                $el.trigger('keyup');
            }, 10);
        },

        /**
         * Show data source helpers
         *
         * @param term {String}
         * @param position {Number}
         * @returns {Object}
         */
        getDataSource: function(term, position) {
            _.each(this.options.dataSource, function(source) {
                (source.$holder || source.$el).hide();
            });

            if (_.isEmpty(term)) {
                return undefined;
            }

            return this.options.dataSource[this._getTermPartUnderCaret(term, position).current];
        },

        /**
         * Returns part of term (like parent.child) under cursor
         *
         * @param term {String}
         * @param position {Number}
         * @returns {{current: (*|String), tail: (*|String)}}
         * @private
         */
        _getTermPartUnderCaret: function(term, position) {
            var path = '';
            var loopBreak = false;

            _.each(term.split('.'), function(part) {
                if (!loopBreak) {
                    path += (_.isEmpty(path) || _.isEmpty(part) ? '' : '.') + part;
                    loopBreak = position <= path.length;
                }
            });

            return {
                current: this._replaceWraps(path, '[]', 'wipe'),
                tail: this._getStringPart(term, path.length)
            };
        },

        /**
         * Shows helper input
         *
         * @param source {Object}
         * @param $returnEl {jQuery}
         */
        showHelper: function(source, $returnEl) {
            if (source.$el && source.$el.length) {
                var elPosition = _.extend({position: 'absolute'}, this._getPosition($returnEl));
                var $el = source.$holder || source.$el;

                $el.css(elPosition);
                $el.show();
            }
        },

        /**
         * Returns object with suggested list and word position.
         *
         * @param value {String}
         * @param caretPosition {Number}
         * @returns {{list: Array, position: ({start: Number, end: Number})}}
         */
        getSuggestData: function(value, caretPosition) {
            var rootData = this.entities || this.options.data;

            if (_.isEmpty(value.trim()) || caretPosition === 0) {
                return {
                    list: this._getMarkedRelationItemsKeys(rootData),
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var normalized = this._getNormalized(value, caretPosition);
            var normWordPosition = this._getWordPosition(normalized.string, normalized.position);

            var underCaret = {
                space: value[caretPosition - 1] === ' ',
                dot: value[caretPosition - 1] === '.',
                word: this._getWordUnderCaret(normalized.string, normWordPosition),
                term: this._getWordPosition(normalized.string, normalized.position, _.assign({space: /\\s/gi}, this.opsRegEx))
            };

            var splitWordTerm = this._splitTermAndExpr(underCaret.word.current).term;

            var isWordsTerm = underCaret.term.start === normWordPosition.start;
            var prevNotBool = underCaret.word.previous &&
                !this._contains(this.options.operations.bool, underCaret.word.previous) &&
                !this._contains(this.options.operations.bool, underCaret.word.current);
            var term = isWordsTerm ? splitWordTerm : this._getWordUnderCaret(normalized.string, underCaret.term).current;
            var inWordPosition = normalized.position - (isWordsTerm ? normWordPosition.start : underCaret.term.start);

            var dataSource = this.getDataSource(term, inWordPosition);

            if (!_.isEmpty(dataSource) && !underCaret.dot && !underCaret.space) {
                this.showHelper(dataSource, this.view.$el);

                return {
                    list: [],
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var cases = [];
            var termPart = this._getStringPart(term, 0, inWordPosition);

            if (!this._checkTermDepth(termPart)) {
                return {
                    list: [],
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var pathValue = this._getValueByPath(termPart, rootData);
            var word = (pathValue || this._contains(this.cases.bool, underCaret.word.current)) && !prevNotBool ? underCaret.word.current : underCaret.word.previous;
            var wordIs = this._getWordData(word);
            var isFullExpression = wordIs.isInclusion || wordIs.isCompare;
            var exprOnly = !this.allowed.compare && !this.allowed.inclusion && !this.allowed.equality;

            if (!prevNotBool && /[a-z.]/gi.test(term) && pathValue && !_.isString(pathValue.type) && !underCaret.space) {
                if (!wordIs.notOps) {
                    cases = this._getMarkedRelationItemsKeys(this._getCasesByType(this._getValueByPath(splitWordTerm, rootData), pathValue), termPart);
                } else {
                    cases = this._getMarkedRelationItemsKeys(pathValue || rootData, termPart);
                }
            } else if (wordIs) {
                if (pathValue && pathValue.type === 'standalone') {
                    cases = this.options.operations.bool;
                } else if (isFullExpression) {
                    cases = _.union(cases, this.options.operations.bool, this.allowed.math ? this.options.operations.math : []);
                } else if (wordIs.isBool) {
                    cases = this._getMarkedRelationItemsKeys(rootData);
                } else if (wordIs.hasTerm && (!wordIs.hasValue || exprOnly)) {
                    cases = _.union(cases, pathValue.type ? this._getOpsByType(pathValue.type) : this._getMarkedRelationItemsKeys(rootData));
                } else if (wordIs.notOps) {
                    cases = this._getMarkedRelationItemsKeys(pathValue || rootData, pathValue ? termPart : '');
                } else if (pathValue && !_.isString(pathValue.type)) {
                    cases = this._getMarkedRelationItemsKeys(pathValue, termPart);
                }
            }

            cases = _.union(this._getPresetCases(splitWordTerm, !wordIs.notOps && !wordIs.hasValue ? this._getValueByPath(splitWordTerm, rootData) : []), cases);

            var termParts = !underCaret.space ? /^(.*)\.(.*)\W?/g.exec(termPart) : null;
            var searchPart = underCaret.space ? '' : (termParts ? (_.isUndefined(termParts[2]) ? termParts[1] : termParts[2]) : termPart);

            return {
                list: this._getSuggestList(cases, searchPart, underCaret.space, underCaret.dot),
                position: {
                    start: caretPosition - searchPart.length,
                    end: caretPosition
                }
            };
        },

        /**
         * Returns the cases that are predefined or obtained upon request for field or type
         *
         * @param name {String}
         * @param data {Object}
         * @returns {*}
         * @private
         */
        _getPresetCases: function(name, data) {
            if (!data) {
                return [];
            }

            switch (data.type) {
                case 'boolean':
                    return ['TRUE', 'FALSE'];
                default:
                    return [];
            }
        },

        /**
         * Adds '&hellip;' to key name if type is 'relation'
         *
         * @param obj {Object}
         * @param [currentPath] {String}
         * @returns {Object}
         * @private
         */
        _getMarkedRelationItemsKeys: function(obj, currentPath) {
            var showRelations = this._checkTermDepth(currentPath, -1);

            return _.compact(_.reduce(obj, function(result, item, key) {
                return _.union(result, [this._markRelationItem(key, item.type, showRelations)]);
            }, [], this));
        },

        /**
         * Adds &hellip; to key name if type is 'relation'
         *
         * @param key {String}
         * @param type {String}
         * @param isAllowedDepth {Boolean}
         * @returns {string}
         * @private
         */
        _markRelationItem: function(key, type, isAllowedDepth) {
            return isAllowedDepth || type !== 'relation' ? key + (type === 'relation' ? '&hellip;' : '') : '';
        },

        /**
         * Returns cases for expression which are suitable to term's type
         *
         * @param termData {Object}
         * @param data {Object}
         * @returns {Object}
         * @private
         */
        _getCasesByType: function(termData, data) {
            if (_.isEmpty(data)) {
                return [];
            }

            var allowedTypes = this._getAllowedTypes(termData.type);

            if (!_.isArray(allowedTypes)) {
                return [];
            }

            return _.isEmpty(allowedTypes) ? data : _.reduce(data, function(result, item, key) {
                    if (!_.isString(item.type) || (_.isString(item.type) && this._contains(allowedTypes, item.type))) {
                        result[key] = item;
                    }
                    return result;
                }, {}, this);
        },

        /**
         * Returns allowed expression which are suitable to term's type
         *
         * @param type {String}
         * @returns {Array}
         * @private
         */
        _getAllowedTypes: function(type) {
            switch (type) {
                case 'boolean':
                    return ['boolean', 'relation'];
                case 'integer':
                    return ['integer', 'relation'];
                case 'string':
                    return ['string', 'relation'];
                case 'float':
                    return ['integer', 'float', 'relation'];
                case 'standalone':
                    return null;
                default:
                    return ['relation'];

                /*
                 case 'enum':
                 break;
                 case 'datetime':
                 break;
                 case 'collection':
                 break;
                 */
            }
        },

        /**
         * Returns operation basel on entity's type
         *
         * @param type {String}
         * @returns {Array}
         * @private
         */
        _getOpsByType: function(type) {
            if (!_.isString(type)) {
                return [];
            }

            var equality = this.allowed.equality ? this.options.operations.equality : [];
            var compare = this.allowed.compare ? this.options.operations.compare : [];
            var inclusion = this.allowed.inclusion ? this.options.operations.inclusion : [];
            var math = this.allowed.math ? this.options.operations.math : [];

            switch (type) {
                case 'string':
                case 'enum':
                case 'boolean':
                    return _.union(equality, inclusion);
                case 'integer':
                case 'float':
                case 'datetime':
                    return _.union(equality, compare, inclusion, math);
                case 'collection':
                    return inclusion;
                case 'standalone':
                    return [];
            }
        },

        /**
         * Getting the word's start position.
         *
         * @param value {String}
         * @param position {Number}
         * @param [re] {RegExp}
         * @returns {{start: (*|Number), end: (*|Number), index: Number, spaces: (*|Array)}}
         * @private
         */
        _getWordPosition: function(value, position, re) {
            var _matches = _.compact(_.uniq(re ? _.flatten(_.map(re, function(regex) {
                    return value.match(regex);
                })) : value.match(/\s+|\(|\)/g)));
            var sepPos = _.union([0], getMatchIndex(value, _matches), [value.length]);
            var index = _.reduce(sepPos, function(memo, item) {
                return item < position ? ++memo : memo;
            }, 0);

            return {
                start: sepPos[index - 1] || 0,
                end: sepPos[index],
                index: index,
                spaces: sepPos
            };

            /**
             * Returns matches positions recursively
             *
             * @param string {String}
             * @param matches {Array}
             * @returns {Array}
             */
            function getMatchIndex(string, matches) {
                var arr = [];

                if (!string.trim()) {
                    return [];
                }

                _.each(matches, function(item) {
                    var indexOf = string.indexOf(item);
                    var startPos = 0;

                    while (indexOf !== -1) {
                        var currPos = indexOf + startPos;
                        var nextPos = currPos + item.length;

                        arr.push(currPos, nextPos);

                        startPos = nextPos;

                        indexOf = string.substr(startPos).indexOf(item);
                    }
                });

                return _.sortBy(arr);
            }
        },

        /**
         * Returns list of matching words.
         *
         * @param pathKeys {Array}
         * @param searchPart {String}
         * @param isSpace {Boolean}
         * @param isDot {Boolean}
         * @returns {Array}
         * @private
         */
        _getSuggestList: function(pathKeys, searchPart, isSpace, isDot) {
            /**
             * Getting list of cases from all subcases.
             *
             * @param word {String}
             * @returns {Array}
             */
            var getCases = _.bind(function(word) {
                var base = Array.prototype.slice.call(arguments, 1);
                var cases = _.union(_.flatten(!_.isEmpty(base) ? base : _.values(this.cases)));

                return this._getFilteredSuggests(cases, word);
            }, this);

            if (_.isEmpty(pathKeys)) {
                return [];
            }

            if ((_.isEmpty(searchPart) && this.options.data === pathKeys) || isDot || isSpace) {
                return pathKeys;
            }

            if (!isDot && !isSpace && searchPart) {
                return getCases(searchPart, pathKeys);
            }

            return [];

        },

        /**
         * Returns word's correspondences.
         *
         * @param word {String}
         * @returns {Object}
         * @private
         */
        _getWordData: function(word) {
            if (_.isEmpty(word)) {
                return {};
            }

            var operations = this.options.operations;
            var checkIt = this._checkWord(word);
            var lastChar = word[word.length - 1];
            var splitWord = this._splitTermAndExpr(word);

            return {
                hasCompare: checkIt.hasCompare,
                hasInclusion: checkIt.hasInclusion,
                isCompare: checkIt.hasCompare && checkIt.isValid,
                isInclusion: checkIt.hasInclusion && checkIt.isValid,
                hasTerm: splitWord && !_.isEmpty(splitWord.term) && !_.isEmpty(this._getValueByPath(splitWord.term).type),
                hasValue: splitWord && !_.isEmpty(splitWord.expr),
                isBool: _.contains(operations.bool, word),
                isMath: _.contains(operations.math, lastChar),
                notOps: !_.some(this.opsRegEx, function(re) {
                    return re.exec(word);
                })
            };
        },

        /**
         * Returns the word's state.
         *
         * @param string {String}
         * @returns {Object}
         * @private
         */
        _checkWord: function(string) {
            var isCompare;
            var isInclusion;
            var isExpression;

            var noOpsAllowed = _.isEmpty(this.allowed);
            var operationType = this._getOperationType(string);
            var hasCompare = operationType.compare || operationType.equality;
            var pathData = this._getValueByPath(string);
            var isTerm = this._checkTerm(string);

            if (hasCompare) {
                isCompare = this._checkCompare(string, operationType.match);
            } else if (operationType.inclusion) {
                isInclusion = this._checkInclusion(string, operationType.match);
            } else if (operationType.math) {
                isExpression = this._checkExpression(string, pathData.type);
            }

            var isValid = noOpsAllowed ? isTerm : (isCompare || isInclusion || isExpression || (pathData && pathData.type === 'standalone'));

            if (isValid) {
                this.error = {};
            } else {
                this.error.word = 'Wrong part in \'' + string + '\'';
            }

            return {
                isValid: isValid,
                hasCompare: hasCompare,
                hasInclusion: operationType.inclusion,
                hasExpression: operationType.math
            };
        },

        /**
         * Checking of accessory word to terms.
         *
         * @param string {String}
         * @returns {Boolean}
         * @private
         */
        _checkTerm: function(string) {
            if (!string) {
                return false;
            }

            var term = this._replaceWraps(string, '[]', 'wipe');
            var dataType = this._getValueByPath(term).type;
            var hasEndType = !_.isEmpty(dataType) && _.isString(dataType) && dataType !== 'relation';

            if (!hasEndType) {
                this.error.term = 'Part \'' + term + '\'' + ' is wrong';
                return false;
            }

            if (this.error.term) {
                delete this.error.term;
            }

            return this._checkTermDepth(string);
        },

        /**
         * Returns boolean whether string is a expression of comparison.
         *
         * @param string {String}
         * @param match {String}
         * @returns {Boolean}
         * @private
         */
        _checkCompare: function(string, match) {
            var matchSplit = this._splitTermAndExpr(string, match);

            return this._checkTerm(matchSplit.term) && this._checkExpression(matchSplit.expr, this._getValueByPath(matchSplit.term).type);
        },

        /**
         * Returns boolean whether string is a expression of inclusion.
         *
         * @param string {String}
         * @param match {String}
         * @returns {Boolean}
         * @private
         */
        _checkInclusion: function(string, match) {
            var matchSplit = this._splitTermAndExpr(string, match);
            var expr = this._replaceWraps(matchSplit.expr, '[]', 'trim');

            return this._checkTerm(matchSplit.term) && !_.isEmpty(matchSplit.expr) && (this._checkArray(matchSplit.expr) || this._checkTerm(expr));
        },

        /**
         * Returns boolean whether string is a array, non empty array, and has valid values.
         *
         * @param array {Array|String}
         * @param [type] {String}
         * @returns {Boolean}
         * @private
         */
        _checkArray: function(array, type) {
            /**
             * Getting array of string matches.
             *
             * @param string {String}
             * @param re {RegExp}
             * @returns {Array}
             */
            var getMatches = function(string, re) {
                var match,
                    matches = [];

                while ((match = re.exec(string)) !== null) {
                    matches.push(match[2]);
                }

                return matches;
            };

            /**
             * Returns boolean whether several values are valid.
             *
             * @param array {Array}
             * @param type {String}
             * @returns {Boolean}
             */
            var checkValues = _.bind(function(array, type) {
                return _.every(array, function(value) {
                    if (_.isEmpty(value)) {
                        this.error.array = 'One of \'' + array + '\' array items is empty';
                        return false;
                    }

                    return this._checkValue(value, type);
                }, this);
            }, this);

            if (_.isArray(array)) {
                return checkValues(array, type);
            } else {
                var arrayMatch = getMatches(array, /^(\[(.*?)\])$/g);

                if (!_.isEmpty(arrayMatch)) {
                    if (arrayMatch.length > 1) {
                        this.error.array = 'Array \'' + array + '\' is wrong';
                        return false;
                    } else {
                        return checkValues((arrayMatch[0] || '').split(','), type);
                    }
                }
            }
        },

        /**
         * Returns boolean whether value is valid: is number, is not NaN, or is a term.
         *
         * @param value {String}
         * @param type {String}
         * @returns {Boolean}
         * @private
         */
        _checkValue: function(value, type) {
            if (_.isEmpty(value)) {
                return false;
            }

            return this._hasValidType(value, type) || this._checkTerm(value);
        },

        /**
         * Returns result of validation of term's depth
         *
         * @param string {String}
         * @param [offset] {Number}
         * @returns {Boolean}
         * @private
         */
        _checkTermDepth: function(string, offset) {
            offset = _.isUndefined(offset) ? 0 : offset;
            return (string || '').split('.').length <= this.options.termLevelLimit + offset;
        },

        /**
         * Returns boolean how value corresponds to specified type
         *
         * @param value {String}
         * @param type {String}
         * @returns {Boolean}
         * @private
         */
        _hasValidType: function(value, type) {
            if (_.isArray(type)) {
                return _.some(type, function(item) {
                    return this._hasValidType(value, item);
                }, this);
            }

            if (!_.isString(type) || (_.isEmpty(value) && type !== 'standalone')) {
                return false;
            }

            var quotesMatch = value.match(/(\'|\")/g);
            var quotesLength = quotesMatch ? quotesMatch.length : 0;
            var wrongQuotesMatch = value.match(/(\'|\"){2,}/g);

            var number = Number(value);
            var isNumber = _.isNumber(number) && _.isFinite(number);

            switch (type) {
                case 'boolean':
                    return !_.isEmpty(value.match(/^(true|false)$/gi));
                case 'integer':
                    return isNumber && number === Math.floor(number);
                case 'float':
                    return isNumber;
                case 'standalone':
                    return _.isEmpty(value);
                default:
                    return _.isString(value) && !_.isNumber(value) && quotesLength % 2 === 0 && !wrongQuotesMatch;

                // TODO add helpers for: enum, datetime, collection
                /*
                 case 'string':
                 return _.isString(value) && !_.isNumber(value) && quotesLength % 2 === 0 && !wrongQuotesMatch;
                 case 'enum':
                 break;
                 case 'datetime':
                 break;
                 case 'collection':
                 break;
                 */
            }
        },

        /**
         * Returns pair of term and expression.
         *
         * @param string {String}
         * @param [splitter] {String}
         * @returns {String|{term: (*|String), expr: (*|String), splitter: (*|String)}}
         * @private
         */
        _splitTermAndExpr: function(string, splitter) {
            var matchSplit;
            var breakLoop = false;

            if (_.isEmpty(string)) {
                return string;
            }

            if (!_.isEmpty(splitter)) {
                matchSplit = this._splitString(string, splitter);
            } else {
                _.map(this.opsRegEx, function(regex) {
                    if (!breakLoop) {
                        var match = string.match(regex);

                        if (match && match.length) {
                            matchSplit = this._splitString(string, match[0]);
                            splitter = match[0];
                            breakLoop = !!matchSplit[2];
                        }
                    }
                }, this);
            }

            return {
                term: matchSplit && !_.isEmpty(matchSplit[0]) ? matchSplit[0] : string,
                expr: matchSplit && !_.isEmpty(matchSplit[1]) ? matchSplit[1] : null,
                splitter: splitter
            };
        },

        /**
         * Returns boolean whether expression is: valid value, or valid array, or belongs to keyData array.
         *
         * @param expr {String}
         * @param [type] {Array}
         * @returns {Boolean}
         * @private
         */
        _checkExpression: function(expr, type) {
            if (!expr) {
                return false;
            }

            if (this._checkValue(expr, type)) {
                return true;
            }

            if (this._checkArray(expr, type)) {
                return true;
            }

            if (this._contains(type, expr)) {
                return true;
            }

            var mathMatch = expr.match(this.opsRegEx.math);

            if (mathMatch) {
                return this._checkArray(_.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' '), this._getAllowedTypes('float'));
            }
        },

        /**
         * Returns boolean for operations type used in string and the matched string.
         *
         * @param string {String}
         * @returns {{equality: Boolean, compare: Boolean, inclusion: Boolean, math: Boolean, match: *}}
         * @private
         */
        _getOperationType: function(string) {
            var compareMatch = string.match(this.opsRegEx.compare);
            var equalityMatch = string.match(this.opsRegEx.equality);
            var inclusionMatch = string.match(this.opsRegEx.inclusion);
            var mathMatch = string.match(this.opsRegEx.math);

            return {
                equality: Boolean(equalityMatch && equalityMatch.length === 1 && this.allowed.equality),
                compare: Boolean(compareMatch && compareMatch.length === 1 && this.allowed.compare),
                inclusion: Boolean(inclusionMatch && inclusionMatch.length === 1 && this.allowed.inclusion),
                math: Boolean(this.allowed.math && !this.allowed.compare && !this.allowed.inclusion),
                match: equalityMatch || compareMatch || inclusionMatch || mathMatch
            };
        },

        /**
         * Returns RegExp object for operations array.
         *
         * @param array {Array}
         * @param name {String}
         * @returns {RegExp}
         * @private
         */
        _getRegexp: function(array, name) {
            if (_.isEmpty(array)) {
                return null;
            }

            var reString;
            var escapedOps = array.map(function(item) {
                return !item.match(/\s|\w/gi) ? '\\' + item.split('').join('\\') : item.replace(/\s+/gi, '\\s?');
            });

            switch (name) {
                case 'bool':
                    reString = '(\\s+(' + escapedOps.join('|') + ')\\s+)';
                    break;
                case 'inclusion':
                    reString = '((\\s+|\\~)(' + escapedOps.join('|') + ')(\\~|\\s+|$))';
                    break;
                default:
                    reString = '((\\s*|\\~)(' + escapedOps.join('|') + ')(\\~|\\s*))';
            }

            return new RegExp(reString, 'g');
        },

        /**
         * Returns normalized value data: string and corrected cursor position.
         *
         * @param value {String}
         * @param [caretPosition] {Number}
         * @returns {{string: String, position: Number}}
         * @private
         */
        _getNormalized: function(value, caretPosition) {
            return {
                string: this._getNormalizedString(value),
                position: this._getNormalizedString(this._getStringPart(value, 0, caretPosition)).length
            };
        },

        /**
         * Returns normalized string
         *
         * @param value {String}
         * @returns {String}
         * @private
         */
        _getNormalizedString: function(value) {
            var string = value;

            string = this._replaceWraps(string, '()', ' ');
            string = string.replace(/\[\s+/g, '[').replace(/\s+\]/g, ']');
            string = string.replace(/\s+,\s+/g, ',');

            _.each(this.opsRegEx, function(re, name) {
                switch (name) {
                    case 'bool':
                        string = string.replace(re, ' $1 ');
                        break;
                    default:
                        string = string.replace(re, function(match) {
                            return '~' + match.replace(/\s+/g, '') + '~';
                        });
                }

                var quotedRegEx = new RegExp(re.source + '\'' + '.*?' + '\'', 'g');
                var arrayRegEx = new RegExp(re.source + '\\\[' + '.*?' + '\\\]', 'g');
                var quotedText = string.match(quotedRegEx);
                var arrayText = string.match(arrayRegEx);

                if (quotedText && quotedText.length) {
                    string = replaceSpacesInArray(quotedText, string);
                }

                if (arrayText && arrayText.length) {
                    string = replaceSpacesInArray(arrayText, string);
                }
            });

            string = replaceSpaces(string, ' ');
            string = string.trim();

            return string;

            function replaceSpaces(text, replace) {
                return text.replace(/\s+/g, replace || '');
            }

            function replaceSpacesInArray(arr, string) {
                _.each(arr, function(item) {
                    string = string.replace(item, replaceSpaces(item, ''));
                });

                return string;
            }
        },

        /**
         * Returns list filtered by the word.
         *
         * @param list {Array}
         * @param word {String}
         * @returns {Array}
         * @private
         */
        _getFilteredSuggests: function(list, word) {
            if (_.isEmpty(word) || _.isEmpty(list)) {
                return list;
            }

            var arr = _.filter(list, function(item) {
                var lowercaseItem = item.toLowerCase();
                var lowercaseWord = word.toLowerCase();

                return lowercaseItem.indexOf(lowercaseWord) === 0 && lowercaseItem !== lowercaseWord;
            });

            return arr.length > 1 || arr[0] !== word ? arr : [];
        },

        /**
         * Returns list of cases based on options.date structure.
         *
         * @param object {Object}
         * @param [baseName] {String}
         * @param [baseData] {Object}
         * @returns {Array}
         * @private
         */
        _getStrings: function(object, baseName, baseData) {
            var arr = [];

            if (_.isArray(object) && !baseName) {
                arr = _.union(arr, object);
            } else {
                _.each(object, function(item, name) {
                    var subName = baseName ? baseName + '.' + name : name;

                    if (!_.contains(arr, baseName)) {
                        arr.push(baseName);
                    }

                    if (item.type === 'array') {
                        arr.push(subName);
                    } else if (_.isObject(item) && !_.isArray(item)) {
                        arr = _.union(arr, this._getStrings(item, name, baseData || object));
                    } else if (!_.isUndefined(baseData) && _.isObject(baseData[name])) {
                        arr = _.union(arr, this._getStrings(baseData[name], subName, baseData || object));
                    } else {
                        arr.push(subName);
                    }
                }, this);
            }

            return _.compact(arr);
        },

        /**
         * Returns part of string from startPos to endPos.
         *
         * @param string {String}
         * @param startPos {Number}
         * @param [endPos] {Number}
         * @returns {String}
         * @private
         */
        _getStringPart: function(string, startPos, endPos) {
            if (_.isNull(startPos) && _.isNull(endPos)) {
                return null;
            }

            var length = _.isNumber(endPos) ? endPos - startPos : undefined;

            return _.isNumber(startPos) ? String(string).substr(startPos, length) : string;
        },

        /**
         * Returns array of string part parted by splitter.
         *
         * @param string {String}
         * @param splitter {String}
         * @returns {Array}
         * @private
         */
        _splitString: function(string, splitter) {
            return _.compact(string.split(splitter));
        },

        /**
         * Returns groups of expressions (even) and booleans (odd) from array of words.
         *
         * @param string {String}
         * @returns {{expr: (*|Array), bool: (*|Array)}}
         * @private
         */
        _getGroups: function(string) {
            return {
                expr: separateGroups(string, true),
                bool: separateGroups(string)
            };

            function separateGroups(groups, isOdd) {
                return _.filter(groups, function(item, i) {
                    var modulo = i % 2;
                    return isOdd ? !modulo : modulo;
                });
            }
        },

        /**
         * Returns boolean when value contains in array. Function is case insensitive of values.
         *
         * @param array {Array}
         * @param string {String}
         * @returns {Boolean}
         * @private
         */
        _contains: function(array, string) {
            if (!_.isArray(array)) {
                return false;
            }

            if (_.isEmpty(string)) {
                return false;
            }

            return _.some(array, function(item) {
                return string.toLowerCase() === item.toLowerCase();
            });
        },

        /**
         * Returns value of object key requested by path.
         *
         * @param path {String}
         * @param [obj] {Object}
         * @returns {undefined|Object}
         * @private
         */
        _getValueByPath: function(path, obj) {
            var getValue = _.bind(function(path, obj) {
                var result = obj || this.entities;
                var pathWay = this._replaceWraps(path, '[]', 'wipe').split('.');

                if (_.isEmpty(path)) {
                    return this.isEntities ? null : obj;
                }

                _.each(pathWay, function(node) {
                    if (result[node]) {
                        if (result[node].type && result[node].type === 'relation') {
                            result = this.fields[result[node].relation_alias];
                        } else {
                            result = result[node];
                        }
                    }
                }, this);

                return result;
            }, this);

            var _path = path || '';
            var pathValue = getValue(_path, obj);
            var termParts = _path[_path.length - 1] !== ' ' ? /^(.*)\.(.*)\W?/g.exec(_path) : null;

            if (!pathValue) {
                pathValue = getValue(termParts ? termParts[1] : '', obj);
            }

            return pathValue;
        },

        /**
         * Returns cleared of brackets. Types: trim, wipe, remove (by default).
         *
         * @param string {String}
         * @param brackets {String}
         * @param [type] {String}
         * @param [replace] {String}
         * @returns {String}
         * @private
         */
        _replaceWraps: function(string, brackets, type, replace) {
            var output;

            if (_.isEmpty(brackets)) {
                return string;
            }

            if (_.isArray(string)) {
                output = [];
                _.each(string, function(string, i) {
                    output[i] = this._replaceWraps(string, brackets, type, replace);
                }, this);
            } else if (_.isArray(brackets)) {
                _.each(brackets, function(item) {
                    output = this._replaceWraps(string, item, type, replace);
                }, this);
            } else {
                output = string ? _replace(string, brackets, type, replace).trim() : string;
            }

            return output;

            function _replace(string, brackets, type, replace) {
                var re;
                var split = brackets.split('');

                switch (type) {
                    case 'trim':
                        re = '^' + '\\' + split.join('(.*?)\\') + '$';
                        replace = _.isUndefined(replace) ? '$1' : replace + '$1' + replace;
                        break;
                    case 'wipe':
                        re = '\\' + split.join('(.*)?\\');
                        break;
                    default:
                        re = '\\' + split.join('|\\');
                }

                return string.replace(new RegExp(re, 'g'), replace || '');
            }
        }
    });

    return RuleEditorComponent;
});
