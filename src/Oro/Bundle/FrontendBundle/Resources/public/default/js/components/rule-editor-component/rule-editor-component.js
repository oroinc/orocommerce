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
                var value = _this.$element.val().trim().replace(/\s+/g, ' ');

                _this.$element.toggleClass('error', !_this.validate(value, _this.options));
            });

            this.initAutocomplete(options.autocomplete_type);
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
            var words = this.splitString(normalized.string, ' ').arr;
            var groups = this.getGroups(words);

            var logicIsValid = _.last(groups.logicWords) !== _.last(words) && _.every(groups.logic, function(item) {
                    return _.contains(this.options.grouping, item);
                });

            var dataWordsAreValid = _.every(groups.dataWords, this.isDataExpression.bind(_this));

            return logicIsValid && dataWordsAreValid;
        },

        /**
         *
         * @returns {{source: source, matcher: matcher, updater: updater}}
         */
        initAutocomplete: function(type) {
            var _position;
            var _this = this;

            switch (type) {
                case 'typeahead':
                    this.$element.typeahead({
                        minLength: 0,
                        source: function(value) {
                            var sourceData = _this.getAutocompleteSource(value || '');

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
                            this.keyup.apply(this, arguments);
                        },
                        lookup: function() {
                            this.query = _this.$element.val() || '';

                            var items = $.isFunction(this.source) ? this.source(this.query, $.proxy(this.process, this)) : this.source;

                            return items ? this.process(items) : this;
                        }
                    });
                    break;
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
                    end: isSpace ? null : separatorsPositions[index] - 1 || position,
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

            if (_.isNull(expressionMatch)) {
                return false;
            }

            var matchSplit = expressionMatch ? this.splitString(string, expressionMatch[0]).arr : null;

            return !_.isEmpty(matchSplit[1]) && _.contains(this.dataWordCases, matchSplit[0]);
        },

        /**
         *
         * @param normalized
         * @param wordUnderCaret
         * @returns {Array}
         */
        getSuggestList: function(normalized, wordUnderCaret) {
            var _this = this;

            var words = this.splitString(normalized.string, ' ').arr;
            var groups = this.getGroups(words);

            if (_.isEmpty(words)){
                return this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }

            var previousWord = words[words.length - 2];
            var lastWord = _.last(words);
            var isLast = wordIs(lastWord);
            var lastIsValid = isLast.logic || isLast.dataWord || isLast.dataExpression;

            var isCheckedWord = lastIsValid || words.length === 1 ? isLast : wordIs(previousWord);

            if (isCheckedWord.dataExpression) {
                return this.getFilteredSuggests(wordUnderCaret, this.logicWordCases);
            } else if (isCheckedWord.dataWord) {
                return this.getFilteredSuggests(wordUnderCaret, this.operationsCases);
            } else {
                return this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }

            function wordIs(word) {
                return {
                    logic: !_.isEmpty(groups.logicWords) && _.contains(_this.logicWordCases, word),
                    dataWord: !_.isEmpty(groups.dataWords) && _.contains(_this.dataWordCases, word),
                    dataExpression: !_.isEmpty(groups.dataWords) && _this.isDataExpression(word)
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
         * @returns {{arr: array, hasParts: boolean}}
         */
        splitString: function(string, splitter) {
            var arr = _.compact(string.split(splitter));

            return {
                arr: arr,
                hasParts: arr.length > 1
            };
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
