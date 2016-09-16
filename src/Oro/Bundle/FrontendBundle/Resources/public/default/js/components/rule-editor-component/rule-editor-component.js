define([
    'jquery',
    'underscore',
    'oroui/js/app/components/base/component'
], function($, _, BaseComponent) {
    'use strict';

    var RuleEditorComponent;

    RuleEditorComponent = BaseComponent.extend({
        options: null,
        $element: null,
        opsRegEx: null,
        dataWordCases: [],
        logicWordCases: [],
        operationsCases: [],

        /**
         *
         *
         *
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$element = this.options._sourceElement.find('input[type="text"]').eq(0);
            this.opsRegEx = this.getRegexp(options.operations);

            this.dataWordCases = this.getPathsArray(options.data);
            this.logicWordCases = this.getPathsArray(options.grouping);
            this.operationsCases = this.getPathsArray(options.operations);

            var _this = this;

            this.$element.on('keyup paste', function() {
                var value = _this.$element.val().trim().replace(/\s+/g, ' ');

                _this.$element.toggleClass('error', !_this.validate(value, _this.options));
            });


            this.$element.typeahead(this.autocomplete(this.$element, this.options));
        },

        validate: function(value, options) {
            if (value === '') {
                return true;
            }

            var _this = this;

            var normalized = this.getNormalized(value, this.opsRegEx);
            var words = this.splitString(normalized.string, ' ').arr;
            var groups = this.getGroups(words);

            var logicIsValid = _.last(groups.logicWords) !== _.last(words) && _.every(groups.logic, function(item) {
                    return _.contains(options.grouping, item);
                });

            var dataWordsAreValid = _.every(groups.dataWords, this.validateDataExpr.bind(_this));

            return logicIsValid && dataWordsAreValid;
        },

        autocomplete: function($element) {
            var wordUnderCaret, caretPosition, normalized, wordPosition;
            var _this = this;

            return {
                source: function(value) {
                    caretPosition = $element[0].selectionStart;

                    normalized = _this.getNormalized(value, _this.opsRegEx, caretPosition);

                    wordPosition = (function(value, position) {
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
                    })(value, caretPosition);

                    wordUnderCaret = _this.getStringPart(value, wordPosition.start, wordPosition.end);

                    return _this.getSuggestList(normalized, wordUnderCaret);
                },
                matcher: function() {
                    return true;
                },
                updater: function(item) {
                    var spacePosition = wordPosition.spaces[wordPosition.index];
                    var cutBefore = _.isNull(wordPosition.start) ? spacePosition : wordPosition.start;
                    var cutAfter = _.isNull(wordPosition.end) ? spacePosition : wordPosition.end;

                    var queryPartBefore = _this.getStringPart(this.query, 0, cutBefore);
                    var queryPartAfter = _this.getStringPart(this.query, cutAfter);

                    setTimeout(function() {
                        $element[0].selectionStart = $element[0].selectionEnd = cutBefore + item.length;
                    }, 10);

                    return queryPartBefore + item + queryPartAfter;
                }
            };
        },

        validateDataExpr: function(item) {
            var expressionMatch = item.match(this.opsRegEx);

            if (_.isNull(expressionMatch)) {
                return false;
            }

            var matchSplit = expressionMatch ? this.splitString(item, expressionMatch[0]).arr : null;

            return !_.isEmpty(matchSplit[1]) && this.validateWord(matchSplit[0], this.dataWordCases);
        },
        validateWord: function(word, ref) {
            return _.contains(ref, word);
        },

        getSuggestList: function(normalized,wordUnderCaret) {
            var _this = this;

            var words = this.splitString(normalized.string, ' ').arr;
            var wordsLength = words.length;
            var groups = this.getGroups(words);

            var previousWord = words[words.length - 2];
            var lastWord = _.last(words);
            var isLast = isWord(lastWord);
            var lastIsValid = isLast.isLogic || isLast.isDataWord || isLast.isValidDataExpr;

            var isCheckedWord = lastIsValid || wordsLength === 1 ? isLast : isWord(previousWord);

            if (isCheckedWord.isValidDataExpr) {
                return this.getFilteredSuggests(wordUnderCaret, this.logicWordCases, true);
            } else if (isCheckedWord.isDataWord) {
                return this.getFilteredSuggests(wordUnderCaret, this.operationsCases, true);
            } else {
                return this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }

            function isWord(word) {
                return {
                    isLogic: !_.isEmpty(groups.logicWords) && _.contains(_this.logicWordCases, word),
                    isDataWord: !_.isEmpty(groups.dataWords) && _this.validateWord(word, _this.dataWordCases),
                    isValidDataExpr: !_.isEmpty(groups.dataWords) && _this.validateDataExpr(word)
                };
            }
        },

        getNormalized: function(value, regex, caretPosition) {
            var string = caretPosition ? this.getStringPart(value, 0, caretPosition) : value;
            var normalizedSpaces = this.clearSpaces(string);
            var normalizedString = this.applyRegexp(normalizedSpaces, regex);

            return {
                string: normalizedString,
                position: normalizedString.length
            };
        },
        clearSpaces: function(string) {
            return string.replace(/\s+/g, ' ');
        },
        applyRegexp: function(string, regex) {
            return string.replace(regex, '$1');
        },

        getFilteredSuggests: function(word, ref, fullOnEmpty) {
            console.log('getFilteredSuggests', word, _.isEmpty(word), ref, fullOnEmpty);

            if (_.isEmpty(word)) {
                return fullOnEmpty ? ref : [];
            }

            var arr = _.filter(ref, function(item) {
                return item.indexOf(word) === 0;
            });

            return arr.length > 1 || arr[0] !== word ? arr : [];
        },

        getPathsArray: function(src, baseName, baseArr) {
            var self = this;
            var arr = [];

            _.each(src, function(item, name) {
                var subName = (baseName ? (baseName + '.') : '') + item;

                if (_.isArray(item)) {
                    arr = _.union(arr, self.getPathsArray(item, name, baseArr || src));
                } else if (baseArr && _.isString(item) && _.isArray(baseArr[item])) {
                    arr = _.union(arr, self.getPathsArray(baseArr[item], subName, baseArr || src));
                } else {
                    arr.push(subName);
                }
            });

            return _.compact(arr);
        },

        getStringPart: function(string, startPos, endPos) {
            if (_.isNull(startPos) && _.isNull(endPos)) {
                return null;
            }

            var length = _.isNumber(endPos) ? endPos - startPos : undefined;

            return _.isNumber(startPos) ? string.substr(startPos, length) : string;
        },

        getRegexp: function(opsArr) {
            if (_.isEmpty(opsArr)) {
                return null;
            }

            var escapedOps = opsArr.map(function(item) {
                return '\\' + item.split('').join('\\');
            });

            return new RegExp('\\s*((' + escapedOps.join(')|(') + '))\\s*', 'gi');
        },

        splitString: function(string, splitter) {
            var arr = _.compact(string.split(splitter));

            return {
                arr: arr,
                hasParts: arr.length > 1
            };
        },

        getGroups: function(words) {
            return {
                dataWords: this.separateGroups(words, true),
                logicWords: this.separateGroups(words)
            };

        },
        separateGroups: function(groups, isOdd) {
            return _.filter(groups, function(item, i) {
                var modulo = i % 2;
                return isOdd ? !modulo : modulo;
            });
        }
    });

    return RuleEditorComponent;
});
