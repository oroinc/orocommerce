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
            allowedOperations: ['math', 'bool', 'equality', 'compare', 'inclusion']
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

            this.isEntities = this.options.entities && !_.isEmpty(entities.fields_data) && !_.isEmpty(entities.root_entities);

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

        _initializeView: function() {
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
            var $el = this.view.$el,
                update = this._getUpdatedData(query, item, position);

            this._setCaretPosition($el, newCaretPosition || update.position || $el[0].selectionStart);

            return update.value;
        },

        /**
         * Initialize helper/additional control
         *
         * @param $viewEl {jQuery}
         */
        initHelper: function($viewEl) {
            var _this = this;

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

                    _this.options.dataSource[key] = source;
                }
            });
            $viewEl.trigger('content:changed');

            /**
             * Adds selected value from helper to main input and sets correct cursor position
             *
             * @param $helperEl {jQuery}
             */
            function setValue($helperEl) {
                var $queryEl = _this.view.$el,
                    query = $queryEl.val(),
                    caretPosition = $queryEl[0].selectionStart,
                    wordPosition = _this._getWordPosition(query, caretPosition),
                    termParts = _this._getTermPartUnderCaret(_this._getWordUnderCaret(query, wordPosition).current, caretPosition - wordPosition.start),
                    changedPart = termParts.current + '[' + $helperEl.val() + ']';

                $helperEl.hide();

                $queryEl.val(_this.setUpdatedValue(query, changedPart + termParts.tail, wordPosition, wordPosition.start + changedPart.length));
            }
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

            var _this = this;

            var normalized = this._getNormalized(value);
            var words = this._splitString(normalized.string, ' ');
            var groups = this._getGroups(words);

            if (!this.allowed.bool && groups.bool.length) {
                return false;
            }

            var logicIsValid = _.last(groups.bool) !== _.last(words) && _.every(groups.bool, function(item) {
                    return _this._contains(_this.options.operations.bool, item);
                });

            var dataWordsAreValid = _.every(groups.expr, function(item) {
                return _this._checkWord(item).isValid;
            });

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
            var _this = this,
                wordPosition = _.isObject(position) ? position : this._getWordPosition(string, position, re),
                currentWord = this._getStringPart(string, wordPosition.start, wordPosition.end),
                prevWord = getPrevWord(1);

            if (!prevWord) {
                prevWord = getPrevWord(2);
            }

            return {
                current: currentWord ? currentWord.trim() : currentWord,
                previous: prevWord
            };

            function getPrevWord(offset) {
                var prevPos = wordPosition.spaces[wordPosition.index - offset - 1];

                return !_.isUndefined(prevPos) ?
                    _this._getStringPart(string, prevPos, wordPosition.spaces[wordPosition.index - offset]).trim() : undefined;
            }
        },

        /**
         * Returns boolean of brackets validation.
         *
         * @param value {String}
         * @returns {Boolean}
         * @private
         */
        _validBrackets: function(value) {
            var breakLoop = false,
                expected = [],
                emptyBrackets = value.match(/(\[\])|(\(\))|(\{\})/gi),
                bracketsOnly = value.replace(/[^\[\](){}]/g, '');

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
            var cutBefore = _.isNull(position.start) ? position.spaces[position.index] : position.start,
                cutAfter = _.isNull(position.end) ? position.spaces[position.index] : position.end,
                stringBefore = this._getStringPart(query, 0, cutBefore),
                stringAfter = this._getStringPart(query, cutAfter);

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
                var offset = 0,
                    stopProcess = false;

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
            var path = '',
                loopBreak = false;

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
            var elPosition, $el;

            if (source.$el && source.$el.length) {
                elPosition = _.extend({position: 'absolute'}, this._getPosition($returnEl));

                $el = source.$holder || source.$el;

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
            var rootData = this.entities || this.options.data,
                charUnderCaret = value[caretPosition - 1],
                isSpaceUnderCaret = charUnderCaret === ' ',
                isDotUnderCaret = charUnderCaret === '.';

            if (_.isEmpty(value.trim()) || caretPosition === 0) {
                return {
                    list: this._getMarkedRelationItemsKeys(rootData),
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var ops = this.options.operations,
                normalized = this._getNormalized(value, caretPosition),
                normWordPosition = this._getWordPosition(normalized.string, normalized.position),
                wordUnderCaret = this._getWordUnderCaret(normalized.string, normWordPosition),

                inWordSepRegex = _.assign({space:/\\s/gi}, this.opsRegEx),
                termUnderCaretPosition = this._getWordPosition(normalized.string, normalized.position, inWordSepRegex),
                termUnderCaret = this._getWordUnderCaret(normalized.string, termUnderCaretPosition),

                splitWord = this._splitTermAndExpr(wordUnderCaret.current),

                isWordsTerm = termUnderCaretPosition.start === normWordPosition.start,
                prevWordIsWord = wordUnderCaret.previous && !this._contains(ops.bool, wordUnderCaret.previous) && !this._contains(ops.bool, wordUnderCaret.current),
                term = isWordsTerm ? splitWord.term : termUnderCaret.current,
                inWordPosition = normalized.position - (isWordsTerm ? normWordPosition.start : termUnderCaretPosition.start);

            var dataSource = this.getDataSource(term, inWordPosition);

            if (!_.isEmpty(dataSource) && !isDotUnderCaret && !isSpaceUnderCaret) {
                this.showHelper(dataSource, this.view.$el);

                return {
                    list: [],
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var cases = [],
                termPart = this._getStringPart(term, 0, inWordPosition),
                pathValue = this._getValueByPath(termPart, rootData),
                termParts = !isSpaceUnderCaret ? /^(.*)\.(.*)\W?/g.exec(termPart) : null,
                word = (pathValue || this._contains(this.cases.bool, wordUnderCaret.current)) && !prevWordIsWord ? wordUnderCaret.current : wordUnderCaret.previous,
                wordIs = this._getWordData(word),
                searchPart = termParts ? (_.isUndefined(termParts[2]) ? termParts[1] : termParts[2]) : termPart,
                isFullWord = wordIs && (wordIs.isInclusion || wordIs.isCompare),
                isTermUnderCaret = /[a-z.]/gi.test(term);

            if (!prevWordIsWord && isTermUnderCaret && pathValue && !_.isString(pathValue.type) && !isSpaceUnderCaret) {
                if (!wordIs.notOps) {
                    cases = this._getMarkedRelationItemsKeys(this._getCasesByType(this._getValueByPath(splitWord.term, rootData), pathValue));
                } else {
                    cases = this._getMarkedRelationItemsKeys(pathValue || rootData);
                }
            } else if (wordIs) {
                if (isFullWord) {
                    cases = ops.bool;
                } else if (wordIs.isInclusion || wordIs.isCompare || (pathValue && pathValue.type === 'standalone' && wordIs.notOps)) {
                    cases = _.union(cases, ops.bool, this.allowed.math ? ops.math : []);
                } else if (wordIs.isBool) {
                    cases = this._getMarkedRelationItemsKeys(rootData);
                } else if (wordIs.hasTerm && !wordIs.hasValue) {
                    cases = _.union(cases, wordIs.notOps ? this._getOpsByType(pathValue.type) : this._getMarkedRelationItemsKeys(rootData));
                } else if (wordIs.notOps) {
                    cases = this._getMarkedRelationItemsKeys(pathValue || rootData);
                } else if (pathValue && !_.isString(pathValue.type)) {
                    cases = this._getMarkedRelationItemsKeys(pathValue);
                }
            }

            cases = _.union(this._getPresetCases(splitWord.term, !wordIs.notOps && !wordIs.hasValue? this._getValueByPath(splitWord.term, rootData) : []), cases);

            if (isSpaceUnderCaret) {
                searchPart = '';
            }

            return {
                list: this._getSuggestList(cases, searchPart, isSpaceUnderCaret, isDotUnderCaret),
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
            if (!data){
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
         * @returns {Object}
         * @private
         */
        _getMarkedRelationItemsKeys: function(obj) {
            return  _.reduce(obj, function(result, item, key) {
                return _.union(result, [this._markRelationItem(key, item.type)]);
            }, [], this);
        },

        /**
         * Adds &hellip; to key name if type is 'relation'
         *
         * @param key {String}
         * @param type {String}
         * @returns {string}
         * @private
         */
        _markRelationItem: function(key, type) {
            return key + (type === 'relation' ? '&hellip;' : '');
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

            var equality = this.allowed.equality ? this.options.operations.equality : [],
                compare = this.allowed.compare ? this.options.operations.compare : [],
                inclusion = this.allowed.inclusion ? this.options.operations.inclusion : [];

            switch (type) {
                case 'string':
                case 'enum':
                case 'boolean':
                    return equality;
                case 'integer':
                case 'float':
                case 'datetime':
                    return _.union(equality, compare);
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
                    })) : value.match(/\s+|\(|\)/g))),
                sepPos = _.union([0], getMatchIndex(value, _matches), [value.length]);

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
                    var indexOf = string.indexOf(item),
                        startPos = 0;

                    while (indexOf !== -1) {
                        var currPos = indexOf + startPos,
                            nextPos = currPos + item.length;

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
            var _this = this;

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

            /**
             * Getting list of cases from all subcases.
             *
             * @param word {String}
             * @returns {Array}
             */
            function getCases(word) {
                var base = Array.prototype.slice.call(arguments, 1),
                    cases = _.union(_.flatten(!_.isEmpty(base) ? base : _.values(_this.cases)));

                return _this._getFilteredSuggests(cases, word);
            }
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

            var operations = this.options.operations,
                checkIt = this._checkWord(word),
                lastChar = word[word.length - 1],
                splitWord = this._splitTermAndExpr(word);

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
            var isCompare, isInclusion, isExpression,
                operationType = this._getOperationType(string),
                hasCompare = operationType.compare || operationType.equality,
                pathData = this._getValueByPath(string);

            if (hasCompare) {
                isCompare = this._checkCompare(string, operationType.match);
            } else if (operationType.inclusion) {
                isInclusion = this._checkInclusion(string, operationType.match);
            } else if (operationType.math) {
                isExpression = this._checkExpression(string);
            }

            var isValid = isCompare || isInclusion || isExpression || (pathData && pathData.type === 'standalone');

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

            var term = this._replaceWraps(string, '[]', 'wipe'),
                hasData = !_.isEmpty(this._getValueByPath(term).type);

            if (!hasData) {
                this.error.term = 'Part \'' + term + '\'' + ' is wrong';
                return false;
            }

            if (this.error.term) {
                delete this.error.term;
            }

            return true;
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

            return this._checkTerm(matchSplit.term) && this._checkExpression(matchSplit.expr, this._getValueByPath(matchSplit.term));
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
            var matchSplit = this._splitTermAndExpr(string, match),
                expr = this._replaceWraps(matchSplit.expr, '[]', 'trim');

            return this._checkTerm(matchSplit.term) && !_.isEmpty(matchSplit.expr) && (this._checkArray(matchSplit.expr) || this._checkTerm(expr));
        },

        /**
         * Returns boolean whether string is a array, non empty array, and has valid values.
         *
         * @param array {Array|String}
         * @param [data] {Object}
         * @returns {Boolean}
         * @private
         */
        _checkArray: function(array, data) {
            var _this = this;

            if (_.isArray(array)) {
                return checkValues(array, data);
            } else {
                var arrayMatch = getMatches(array, /^(\[(.*?)\])$/g);

                if (!_.isEmpty(arrayMatch)) {
                    if (arrayMatch.length > 1) {
                        this.error.array = 'Array \'' + array + '\' is wrong';
                        return false;
                    } else {
                        return checkValues((arrayMatch[0] || '').split(','), data);
                    }
                }
            }

            /**
             * Getting array of string matches.
             *
             * @param string {String}
             * @param re {RegExp}
             * @returns {Array}
             */
            function getMatches(string, re) {
                var match,
                    matches = [];

                while ((match = re.exec(string)) !== null) {
                    matches.push(match[2]);
                }

                return matches;
            }

            /**
             * Returns boolean whether several values are valid.
             *
             * @param array {Array}
             * @param data {Object}
             * @returns {Boolean}
             */
            function checkValues(array, data) {
                return _.every(array, function(value) {
                    if (_.isEmpty(value)) {
                        _this.error.array = 'One of \'' + array + '\' array items is empty';
                        return false;
                    }

                    return _this._checkValue(value, data);
                });
            }
        },

        /**
         * Returns boolean whether value is valid: is number, is not NaN, or is a term.
         *
         * @param value {String}
         * @param data {Object}
         * @returns {Boolean}
         * @private
         */
        _checkValue: function(value, data) {
            if (_.isEmpty(value)) {
                return false;
            }

            return this._hasValidType(value, data.type) || this._checkTerm(value);
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
            if (_.isEmpty(value) && type !== 'standalone') {
                return false;
            }

            var quotesMatch = value.match(/(\'|\")/g),
                quotesLength = quotesMatch ? quotesMatch.length : 0,
                wrongQuotesMatch = value.match(/(\'|\"){2,}/g);

            var number = Number(value),
                isNumber = _.isNumber(number) && _.isFinite(number);

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
            var matchSplit,
                _this = this,
                breakLoop = false;

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
                            matchSplit = _this._splitString(string, match[0]);
                            splitter = match[0];
                            breakLoop = !!matchSplit[2];
                        }
                    }
                });
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
         * @param [keyData] {Array}
         * @returns {Boolean}
         * @private
         */
        _checkExpression: function(expr, keyData) {
            if (!expr) {
                return false;
            }

            if (this._checkValue(expr, keyData)) {
                return true;
            }

            if (this._checkArray(expr, keyData)) {
                return true;
            }

            if (this._contains(keyData, expr)) {
                return true;
            }

            var mathMatch = expr.match(this.opsRegEx.math);

            if (mathMatch) {
                return this._checkArray(_.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' '), keyData);
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
            var compareMatch = string.match(this.opsRegEx.compare),
                equalityMatch = string.match(this.opsRegEx.equality),
                inclusionMatch = string.match(this.opsRegEx.inclusion),
                mathMatch = string.match(this.opsRegEx.math),
                doEquality = equalityMatch && equalityMatch.length === 1 && this.allowed.equality,
                doCompare = compareMatch && compareMatch.length === 1 && this.allowed.compare,
                doInclusion = inclusionMatch && inclusionMatch.length === 1 && this.allowed.inclusion,
                doMath = this.allowed.math && !this.allowed.compare && !this.allowed.inclusion,
                match = equalityMatch || compareMatch || inclusionMatch || mathMatch;

            return {
                equality: Boolean(doEquality),
                compare: Boolean(doCompare),
                inclusion: Boolean(doInclusion),
                math: Boolean(doMath),
                match: match
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

            var reString,
                escapedOps = array.map(function(item) {
                    if (!item.match(/\s|\w/gi)) {
                        return '\\' + item.split('').join('\\');
                    } else {
                        return item.replace(/\s+/gi, '\\s?');
                    }
                });

            switch (name) {
                case 'bool':
                    reString = '(\\s+(' + escapedOps.join('|') + ')\\s+)';
                    break;
                case 'inclusion':
                    reString = '((\\s+|\\~)(' + escapedOps.join('|') + ')(\\~|\\s+))';
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
                var lowercaseItem = item.toLowerCase(),
                    lowercaseWord = word.toLowerCase();

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
            var _this = this,
                arr = [];

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
                        arr = _.union(arr, _this._getStrings(item, name, baseData || object));
                    } else if (!_.isUndefined(baseData) && _.isObject(baseData[name])) {
                        arr = _.union(arr, _this._getStrings(baseData[name], subName, baseData || object));
                    } else {
                        arr.push(subName);
                    }
                });
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
            path = path || '';

            var _this = this,
                pathValue = getValue(path, obj),
                lastCharIsSpace = path[path.length - 1] === ' ',
                termParts = !lastCharIsSpace ? /^(.*)\.(.*)\W?/g.exec(path) : null;

            if (!pathValue) {
                pathValue = getValue(termParts ? termParts[1] : '', obj);
            }

            return pathValue;

            function getValue(path, obj) {
                var result = obj || _this.entities,
                    pathWay = _this._replaceWraps(path, '[]', 'wipe').split('.');

                if (_.isEmpty(path)) {
                    return _this.isEntities ? null : obj;
                }

                _.each(pathWay, function(node) {
                    if (result[node]) {
                        if (result[node].type && result[node].type === 'relation') {
                            result = this.fields[result[node].relation_alias];
                        } else {
                            result = result[node];
                        }
                    }
                }, _this);

                return result;
            }
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
            var output,
                _this = this;

            if (_.isEmpty(brackets)) {
                return string;
            }

            if (_.isArray(string)) {
                output = [];
                _.each(string, function(string, i) {
                    output[i] = _this._replaceWraps(string, brackets, type, replace);
                });
            } else if (_.isArray(brackets)) {
                _.each(brackets, function(item) {
                    output = _this._replaceWraps(string, item, type, replace);
                });
            } else {
                output = string ? _replace(string, brackets, type, replace).trim() : string;
            }

            return output;

            function _replace(string, brackets, type, replace) {
                var split = brackets.split('');

                switch (type) {
                    case 'trim':
                        return string.replace(new RegExp('^' + '\\' + split.join('(.*?)\\') + '$', 'g'), _.isUndefined(replace) ? '$1' : replace + '$1' + replace);
                    case 'wipe':
                        return string.replace(new RegExp('\\' + split.join('(.*)?\\'), 'g'), replace || '');
                    default:
                        return string.replace(new RegExp('\\' + split.join('|\\'), 'g'), replace || '');
                }
            }
        }
    });

    return RuleEditorComponent;
});
