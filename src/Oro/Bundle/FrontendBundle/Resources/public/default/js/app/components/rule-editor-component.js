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
                bool: ['and', 'or'],
                compare: ['=', '!=', '>', '<', '<=', '>=', 'like'],
                inclusion: ['in', 'not in']
            },
            allowedOperations: ['math', 'bool', 'compare', 'inclusion']
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

            this.options = _.defaults(options || {}, this.options);
            this.options.view = this.options.view || this.view;
            this.options.component = this;

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
         * @param newCaretPosition {Number}
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
         * @returns {*|String}
         * @private
         */
        _getWordUnderCaret: function(string, position) {
            var wordPosition = _.isObject(position) ? position : this._getWordPosition(string, position),
                currentWord = this._getStringPart(string, wordPosition.start, wordPosition.end),
                prevWord = !_.isUndefined(wordPosition.spaces[wordPosition.index - 2]) ?
                    this._getStringPart(string, wordPosition.spaces[wordPosition.index - 2], wordPosition.spaces[wordPosition.index - 1]) : undefined;

            return {
                current: currentWord,
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
            var bracketsOnly = value.replace(/[^\[\](){}]/g, '');

            _.each(bracketsOnly, function(char) {
                if (!breakLoop) {
                    setOpen(char, '(', ')');
                    setOpen(char, '[', ']');
                    checkClose(char, ')');
                    checkClose(char, ']');
                }
            });

            if (breakLoop || !_.isEmpty(expected)) {
                this.error.brackets = 'Wrong brackets balance in \'' + value + '\'';
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
             * Check expected 'close' bracket and set 'unloop' if not.
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
         * @param position {String}
         * @returns {{value: String, position: Object}}
         * @private
         */
        _getUpdatedData: function(query, item, position) {
            var cutBefore = _.isNull(position.start) ? position.spaces[position.index] : position.start;
            var cutAfter = _.isNull(position.end) ? position.spaces[position.index] : position.end;

            var stringBefore = this._getStringPart(query, 0, cutBefore);
            var stringAfter = this._getStringPart(query, cutAfter);

            var doSpaceOffset = _.isString(this._getValueByPath(this.options.data, item));

            return {
                value: stringBefore + item + (doSpaceOffset ? ' ' + stringAfter : stringAfter),
                position: cutBefore + item.length + (doSpaceOffset ? 1 : 0)
            };
        },

        /**
         * Returns position for absolute positioned element (select2)
         *
         * @param $el {jQuery}
         * @returns {{top: Number, left Number}}
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
                source.$el.hide();
            });

            if (_.isEmpty(term)) {
                return undefined;
            }

            return this._getValueByPath(this.options.dataSource, this._getTermPartUnderCaret(term, position).current);
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

                $el = source.$el;

                $el.css(elPosition);
                $el.show();
            }
        },

        /**
         * Returns object with suggested list and word position.
         *
         * @param value {String}
         * @param caretPosition {Number}
         * @returns {{list: array, position: ({start: number, end: number, index: number, spaces: array})}}
         */
        getSuggestData: function(value, caretPosition) {
            var termsData = this.options.data;

            if (_.isEmpty(value.trim()) || caretPosition === 0) {
                return {list: _.keys(termsData), position: {start: caretPosition, end: caretPosition}};
            }

            var normalized = this._getNormalized(value, caretPosition),
                normWordPosition = this._getWordPosition(normalized.string, normalized.position),
                processedWord = this._getWordUnderCaret(normalized.string, normWordPosition),
                splitWord = this._splitTermAndExpr(processedWord.current),
                term = splitWord.term,
                inWordPosition = normalized.position - normWordPosition.start,
                termPart = this._getStringPart(term, 0, inWordPosition),
                charUnderCaret = value[caretPosition - 1],
                lastCharIsSpace = charUnderCaret === ' ',
                lastCharIsDot = charUnderCaret === '.';

            var dataSource = this.getDataSource(term, inWordPosition),
                hasDataSource = !_.isEmpty(dataSource);

            if (hasDataSource && !lastCharIsDot && !lastCharIsSpace) {
                this.showHelper(dataSource, this.view.$el);

                return {
                    list: [],
                    position: {start: caretPosition, end: caretPosition}
                };
            }

            var pathValue = this._getValueByPath(termsData, term),
                termParts = !lastCharIsSpace ? /^(.*)\.(.*)\W?/g.exec(termPart) : null;

            if (!pathValue) {
                pathValue = this._getValueByPath(termsData, termParts ? termParts[1] : '');
            }

            var cases = [],
                word = this._contains(this.cases.data, term) || this._contains(this.cases.bool, processedWord.current) ? processedWord.current : processedWord.previous,
                wordIs = this._getWordData(word),
                searchPart = termParts ? (_.isUndefined(termParts[2]) ? termParts[1] : termParts[2]) : (wordIs && wordIs.hasTerm ? '' : termPart),
                isFullWord = wordIs && (wordIs.isInclusion || wordIs.isCompare);

            if (lastCharIsSpace && wordIs) {
                if (isFullWord) {
                    cases = this.options.operations.bool;
                } else if (wordIs.isInclusion || wordIs.isCompare) {
                    cases = this.options.operations.bool;

                    if (this.allowed.math) {
                        cases = _.union(cases, this.options.operations.math);
                    }
                } else if (wordIs.isBool) {
                    cases = _.keys(termsData);
                } else if (wordIs.hasTerm && !wordIs.hasValue && wordIs.notOps) {
                    if (this.allowed.compare) {
                        cases = _.union(cases, this.options.operations.compare);
                    }
                    if (this.allowed.inclusion) {
                        cases = _.union(cases, this.options.operations.inclusion);
                    }
                    if (!this.allowed.inclusion && !this.allowed.compare) {
                        cases = _.union(cases, this.options.operations.math);
                    }
                } else if (wordIs.notOps) {
                    cases = _.keys(pathValue && pathValue !== 'any' ? pathValue : termsData);
                }
            } else {
                cases = _.keys(pathValue);
            }

            return {
                list: this._getSuggestList(cases, searchPart, lastCharIsSpace, lastCharIsDot),
                position: {
                    start: caretPosition - searchPart.length,
                    end: caretPosition
                }
            };
        },

        /**
         * Getting the word's start position.
         *
         * @param value {String}
         * @param position {Number}
         * @returns {{start: (*|number), end: *, index: number, spaces: (Interval1d|Rectangle)}}
         * @private
         */
        _getWordPosition: function(value, position) {
            var index = 0,
                separatorsPositions = _.compact(value.split('').map(function(char, i) {
                    return (/\s|\(|\)/.test(char)) ? i + 1 : null;
                })),
                separators = _.union([0], separatorsPositions, [value.length]);

            while (separators[index] < position) {
                index++;
            }

            return {
                start: separators[index - 1] || 0,
                end: separators[index],
                index: index,
                spaces: separators
            };
        },

        /**
         * Returns list of matching words.
         *
         * @param pathKeys {Object}
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

            if (!isDot && !isSpace && searchPart){
                return getCases(searchPart, pathKeys);
            }

            return [];

            /**
             * Getting list of cases from all subcases.
             *
             * @param word {String}
             * @returns {*|Array}
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
         * @returns {*}
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
                hasTerm: splitWord && !_.isEmpty(splitWord.term),
                hasValue: splitWord && !_.isEmpty(splitWord.expr),
                isBool: _.contains(operations.bool, word),
                isMath: _.contains(operations.math, lastChar),
                notOps: !_.some(this.opsRegEx, function(re){
                    return re.exec(word);
                })
            };
        },

        /**
         * Returns the word's state.
         *
         * @param string {String}
         * @returns {*}
         * @private
         */
        _checkWord: function(string) {
            var isCompare, isInclusion, isExpression;
            var operationType = this._getOperationType(string);

            if (operationType.compare) {
                isCompare = this._checkCompare(string, operationType.match);
            } else if (operationType.inclusion) {
                isInclusion = this._checkInclusion(string, operationType.match);
            } else if (operationType.math) {
                isExpression = this._checkExpression(string);
            }

            var isValid = isCompare || isInclusion || isExpression;

            if (isValid) {
                this.error = {};
            } else {
                this.error.word = 'Wrong part in \'' + string + '\'';
            }

            return {
                isValid: isValid,
                hasCompare: operationType.compare,
                hasInclusion: operationType.inclusion,
                hasExpression: operationType.math
            };
        },

        /**
         * Checking of accessory word to terms.
         *
         * @param term {String}
         * @param base {Array}
         * @returns {*}
         * @private
         */
        _checkTerm: function(term, base) {
            if (!term) {
                return false;
            }

            if (!this._contains(base, this._replaceWraps(term, '[]', 'wipe'))) {
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
         * @private
         */
        _checkCompare: function(string, match) {
            var matchSplit = this._splitTermAndExpr(string, match),
                pathValue = this._getValueByPath(this.options.data, matchSplit.term);

            return this._checkTerm(matchSplit.term, this.cases.data) && this._checkExpression(matchSplit.expr, pathValue);
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

            return this._checkTerm(matchSplit.term, this.cases.data) && !_.isEmpty(matchSplit.expr) && (this._checkArray(matchSplit.expr) || this._checkTerm(expr, this.cases.data));
        },

        /**
         * Returns boolean whether string is a array, non empty array, and has valid values.
         *
         * @param array {Array}
         * @returns {boolean}
         * @private
         */
        _checkArray: function(array) {
            var _this = this;

            if (_.isArray(array)) {
                return checkValues(array);
            } else {
                var arrayMatch = getMatches(array, /^(\[(.*?)\])$/g);

                if (!_.isEmpty(arrayMatch)) {
                    if (arrayMatch.length > 1) {
                        this.error.array = 'Array \'' + array + '\' is wrong';
                        return false;
                    } else {
                        return checkValues((arrayMatch[0] || '').split(','));
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
             * @returns {Boolean}
             */
            function checkValues(array) {
                return _.every(array, function(value) {
                    if (_.isEmpty(value)) {
                        _this.error.array = 'One of \'' + array + '\' array items is empty';
                        return false;
                    }

                    return _this._checkValue(value);
                });
            }
        },

        /**
         * Returns boolean whether value is valid: is number, is not NaN, or is a term.
         *
         * @param value {String}
         * @returns {Boolean}
         * @private
         */
        _checkValue: function(value) {
            if (_.isEmpty(value)) {
                return false;
            }

            var num = Number(value),
                isNumber = _.isNumber(num) && !_.isNaN(num),
                isString = _.isString(value),
                quotesMatch = value.match(/(\'|\")/g),
                wrongQuotesMatch = value.match(/(\'|\"){2,}/g),
                isValidString = isString && quotesMatch && quotesMatch.length % 2 === 0 && !wrongQuotesMatch;

            return isNumber || isValidString || this._checkTerm(value, this.cases.data);
        },

        /**
         * Returns pair of term and expression.
         *
         * @param string {String}
         * @param splitter {String}
         * @returns {{term: *, expr: *}}
         * @private
         */
        _splitTermAndExpr: function(string, splitter) {
            var matchSplit,
                _this = this,
                breakLoop = false;

            if (_.isEmpty(string)){
                return string;
            }

            if (_.isEmpty(splitter)) {
                _.map(this.opsRegEx, function(regex) {
                    var regexMatch = string.match(regex);

                    if (!breakLoop && regexMatch && regexMatch.length) {
                        matchSplit = _this._splitString(string, regexMatch[0]);
                        breakLoop = !!matchSplit[2];
                    }
                });
            } else {
                matchSplit = this._splitString(string, splitter);
            }

            return {
                term: matchSplit && !_.isEmpty(matchSplit[0]) ? matchSplit[0] : string,
                expr: matchSplit && !_.isEmpty(matchSplit[1]) ? matchSplit[1] : null
            };
        },

        /**
         * Returns boolean whether expression is: valid value, or valid array, or belongs to keyData array.
         *
         * @param string {String}
         * @param keyData {Array}
         * @returns {*}
         * @private
         */
        _checkExpression: function(string, keyData) {
            if (!string) {
                return false;
            }

            if (this._checkValue(string)) {
                return true;
            }

            if (this._checkArray(string)) {
                return true;
            }

            if (this._contains(keyData, string)) {
                return true;
            }

            var mathMatch = string.match(this.opsRegEx.math);

            if (mathMatch) {
                return this._checkArray(_.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, string).split(' '));
            }
        },

        /**
         * Returns boolean for operations type used in string and the matched string.
         *
         * @param string {String}
         * @returns {{compare: (*|boolean), inclusion: (*|boolean), math: (*|boolean), match: undefined}}
         * @private
         */
        _getOperationType: function(string) {
            var compareMatch = string.match(this.opsRegEx.compare),
                inclusionMatch = string.match(this.opsRegEx.inclusion),
                mathMatch = string.match(this.opsRegEx.math),
                doCompare = compareMatch && compareMatch.length === 1 && this.allowed.compare,
                doInclusion = inclusionMatch && inclusionMatch.length === 1 && this.allowed.inclusion,
                doMath = this.allowed.math && !this.allowed.compare && !this.allowed.inclusion,
                match = doCompare ? compareMatch : (doInclusion ? inclusionMatch : (doMath ? mathMatch : undefined));

            return {
                compare: doCompare,
                inclusion: doInclusion,
                math: doMath,
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
                    reString = '\\s+(' + escapedOps.join('|') + ')\\s+';
                    break;
                default:
                    reString = '(\\s*|\\~+)(' + escapedOps.join('|') + ')(\\~+|\\s*)';
            }

            return new RegExp(reString, 'g');
        },

        /**
         * Returns normalized value data: string and corrected cursor position.
         *
         * @param value {String}
         * @param caretPosition {Number}
         * @returns {{string: string, position: number}}
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
         * @param baseName {String}
         * @param baseData {Object}
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
         * @param endPos {Number}
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
         * @returns {{expr: *, bool: *}}
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

            if ( _.isEmpty(string)) {
                return false;
            }

            return _.some(array, function(item) {
                return string.toLowerCase() === item.toLowerCase();
            });
        },

        /**
         * Returns value of object key requested by path.
         *
         * @param obj {Object}
         * @param path {String}
         * @returns {undefined|Object}
         * @private
         */
        _getValueByPath: function(obj, path) {
            var result;

            if (_.isEmpty(path)) {
                return obj;
            }

            _.each(this._replaceWraps(path, '[]', 'wipe').split('.'), function(node) {
                result = (result || obj)[node];
            });

            return result;

        },

        /**
         * Returns cleared of brackets. Types: trim, wipe, remove (by default).
         *
         * @param string {String}
         * @param brackets {String}
         * @param type {String}
         * @param replace {String}
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
