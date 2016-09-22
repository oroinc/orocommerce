define([
    'jquery',
    'underscore',
    'oroui/js/app/components/base/component'
], function($, _, BaseComponent) {
    'use strict';

    var RuleEditorComponent;

    RuleEditorComponent = BaseComponent.extend({
        /**
         *
         * @property {Object}
         */
        options: null,

        /**
         *
         * @property {jQuery}
         */
        $element: null,

        /**
         *
         * @property {RegExp}
         */
        opsRegEx: null,

        /**
         *
         * @property {Array}
         */
        dataWordCases: [],

        /**
         *
         * @property {Array}
         */
        logicWordCases: [],

        /**
         *
         * @property {Array}
         */
        operationsCases: [],

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$element = this.options._sourceElement.find(options.input).eq(0);
            this.opsRegEx = this.getRegexp(options.operations);

            this.dataWordCases = this.getStrings(options.data);
            this.logicWordCases = this.getStrings(options.grouping);
            this.operationsCases = this.getStrings(options.operations);

            var _this = this;

            this.$element.on('keyup paste change', function() {
                _this.$element.toggleClass('error', !_this.validate(_this.$element.val(), _this.options));
            });

            this.initAutocomplete();
        },

        /**
         *
         * @param value
         * @returns {Boolean}
         */
        validate: function(value) {
            if (value === '') {
                return true;
            }

            var _this = this;

            var normalized = this.getNormalized(value, this.opsRegEx);
            var words = this.splitString(normalized.string, ' ');
            var groups = this.getGroups(words);

            var logicIsValid = _.last(groups.logicWords) !== _.last(words) && _.every(groups.logic, function(item) {
                    return _.contains(this.options.grouping, item);
                });

            var dataWordsAreValid = _.every(groups.dataWords, function(item) {
                return _this.isDataExpression(item).full;
            });

            return logicIsValid && dataWordsAreValid;
        },

        initAutocomplete: function() {
            var clickHandler;
            var _context;
            var _position;
            var _this = this;

            clickHandler = function() {
                var _this = this;
                var _arguments = arguments;

                setTimeout(function() {
                    _this.keyup.apply(_this, _arguments);
                }, 10);
            };

            _context = this.$element.typeahead({
                minLength: 0,
                source: function(value) {
                    var sourceData = _this.getAutocompleteSource(value || '');

                    clickHandler = clickHandler.bind(this);

                    _position = sourceData.position;

                    return sourceData.array;
                },
                matcher: function() {
                    return true;
                },
                updater: function(item) {
                    return _this.getUpdateValue(this.query, item, _position);
                },
                focus: function(e) {
                    this.focused = true;
                    clickHandler.apply(this, arguments);
                },
                lookup: function() {
                    this.query = _this.$element.val() || '';

                    var items = $.isFunction(this.source) ? this.source(this.query, $.proxy(this.process, this)) : this.source;

                    return items ? this.process(items) : this;
                }
            });

            if (_context) {
                this.$element.on('click', function() {
                    clickHandler.apply(_context, arguments);
                });
            }
        },

        /**
         *
         * @param query
         * @param item
         * @param position
         * @returns {*}
         */
        getUpdateValue: function(query, item, position) {
            var _this = this;

            var cutBefore = _.isNull(position.start) ? position.spaces[position.index] : position.start;
            var cutAfter = _.isNull(position.end) ? position.spaces[position.index] : position.end;

            var queryPartBefore = this.getStringPart(query, 0, cutBefore);
            var queryPartAfter = this.getStringPart(query, cutAfter);

            setTimeout(function() {
                _this.$element[0].selectionStart = _this.$element[0].selectionEnd = cutBefore + item.length;
            }, 10);

            return queryPartBefore + item + queryPartAfter;
        },

        /**
         *
         * @param value
         * @returns {{array: (*|Array), position: (*|{start: *, end: *, index: number, spaces: array})}}
         */
        getAutocompleteSource: function(value) {
            var caretPosition = this.$element[0].selectionStart;
            var normalized = this.getNormalized(value, this.opsRegEx, caretPosition);
            var wordPosition = this.getWordPosition(value, caretPosition);
            var wordUnderCaret = this.getStringPart(value, wordPosition.start, wordPosition.end);

            return {
                array: this.getSuggestList(normalized, wordUnderCaret),
                position: wordPosition
            };
        },

        /**
         *
         * @param value {String}
         * @param position {Number}
         * @returns {{start: *, end: *, index: number, spaces: array}}
         */
        getWordPosition: function(value, position) {
            var index = 0;
            var result = {
                start: 0,
                end: position
            };
            var separatorsPositions = _.compact(value.split('').map(function(char, i) {
                return (/^\s$/.test(char)) ? i + 1 : null;
            }));


            if (separatorsPositions.length) {
                while (separatorsPositions[index] < position) {
                    index++;
                }

                var isSpace = separatorsPositions[index] === position;

                result = {
                    start: isSpace ? null : separatorsPositions[index - 1] || 0,
                    end: isSpace ? null : position,
                    index: index,
                    spaces: separatorsPositions
                };
            }

            return result;
        },

        /**
         *
         * @param string
         * @returns {Boolean}
         */
        isDataExpression: function(string) {
            var expressionMatch = string.match(this.opsRegEx);

            if (_.isNull(expressionMatch) || expressionMatch.length > 1) {
                return false;
            }

            var matchSplit = expressionMatch ? this.splitString(string, expressionMatch[0]) : null;
            var isValidWord = _.contains(this.dataWordCases, matchSplit[0]);

            return {
                full: !_.isEmpty(matchSplit[1]) && isValidWord,
                noValue: _.isEmpty(matchSplit[1]) && isValidWord
            };
        },

        /**
         *
         * @param normalized
         * @param wordUnderCaret
         * @returns {Array}
         */
        getSuggestList: function(normalized, wordUnderCaret) {
            var result = [];
            var _this = this;

            var words = this.splitString(normalized.string, ' ');
            var groups = this.getGroups(words);

            if (_.isEmpty(normalized.string.trim())) { // initial suggestion for empty value
                return this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }

            var previousWord = words[words.length - 2];
            var lastWord = _.last(words);
            var isLast = wordIs(lastWord);
            var lastIsValid = isLast.logic || isLast.dataWord || isLast.dataExpression;
            var isCheckedWord = lastIsValid || words.length === 1 ? isLast : wordIs(previousWord);

            if (isCheckedWord.dataExpression) { // previous word is a full valid expression of data
                result = this.getFilteredSuggests(wordUnderCaret, this.logicWordCases);
            } else if (isCheckedWord.dataWord) { // previous word is a valid word of data but no full expression
                result = this.getFilteredSuggests(wordUnderCaret, this.operationsCases);
            } else if (!isCheckedWord.operation) { // previous word is a operation (=, !=, etc.)
                result = this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }

            return result;

            function wordIs(word) {
                return {
                    logic: !_.isEmpty(groups.logicWords) && _.contains(_this.logicWordCases, word),
                    dataWord: !_.isEmpty(groups.dataWords) && _.contains(_this.dataWordCases, word),
                    dataExpression: !_.isEmpty(groups.dataWords) && _this.isDataExpression(word).full,
                    operation: _this.isDataExpression(word).noValue
                };
            }
        },

        /**
         *
         * @param value
         * @param regex
         * @param caretPosition
         * @returns {{string: string, position: number}}
         */
        getNormalized: function(value, regex, caretPosition) {
            var string = caretPosition ? this.getStringPart(value, 0, caretPosition) : value;
            var normalizedSpaces = string.replace(/\s+/g, ' ');
            var normalizedString = normalizedSpaces.replace(regex, '$1');

            return {
                string: normalizedString,
                position: normalizedString.length
            };
        },

        /**
         *
         * @param word
         * @param ref
         * @returns {*}
         */
        getFilteredSuggests: function(word, ref) {
            if (_.isEmpty(word)) {
                return ref;
            }

            var arr = _.filter(ref, function(item) {
                return item.indexOf(word) === 0;
            });

            return arr.length > 1 || arr[0] !== word ? arr : [];
        },

        /**
         *
         * @param src
         * @param baseName
         * @param baseArr
         * @returns {Array}
         */
        getStrings: function(src, baseName, baseArr) {
            var self = this;
            var arr = [];

            _.each(src, function(item, name) {
                var subName = baseName ? baseName + '.' + item : item;

                if (_.isArray(item)) {
                    arr = _.union(arr, self.getStrings(item, name, baseArr || src));
                } else if (baseArr && _.isArray(baseArr[item])) {
                    arr = _.union(arr, self.getStrings(baseArr[item], subName, baseArr || src));
                } else if (_.isString(item)) {
                    arr.push(subName);
                }
            });

            return _.compact(arr);
        },

        /**
         *
         * @param string
         * @param startPos
         * @param endPos
         * @returns {String}
         */
        getStringPart: function(string, startPos, endPos) {
            if (_.isNull(startPos) && _.isNull(endPos)) {
                return null;
            }

            var length = _.isNumber(endPos) ? endPos - startPos : undefined;

            return _.isNumber(startPos) ? string.substr(startPos, length) : string;
        },

        /**
         *
         * @param opsArr
         * @returns {RegExp}
         */
        getRegexp: function(opsArr) {
            if (_.isEmpty(opsArr)) {
                return null;
            }

            var escapedOps = opsArr.map(function(item) {
                return '\\' + item.split('').join('\\');
            });

            return new RegExp('\\s*((' + escapedOps.join(')|(') + '))\\s*', 'gi');
        },

        /**
         *
         * @param string
         * @param splitter
         * @returns {Array}
         */
        splitString: function(string, splitter) {
            return  _.compact(string.split(splitter));
        },

        /**
         *
         * @param words
         * @returns {{dataWords: *, logicWords: *}}
         */
        getGroups: function(words) {
            return {
                dataWords: separateGroups(words, true),
                logicWords: separateGroups(words)
            };

            function separateGroups(groups, isOdd) {
                return _.filter(groups, function(item, i) {
                    var modulo = i % 2;
                    return isOdd ? !modulo : modulo;
                });
            }
        }
    });

    return RuleEditorComponent;
});
