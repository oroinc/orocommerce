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

            var dataWordsAreValid = _.every(groups.dataWords, function(item) {
                var expressionMatch = item.match(_this.opsRegEx);
                var matchSplit = expressionMatch ? _this.splitString(item, expressionMatch[0]).arr : null;

                return !_.isNull(expressionMatch) && matchSplit[1] !== '' && _.contains(_this.dataWordCases, matchSplit[0]);
            });

            return logicIsValid && dataWordsAreValid;
        },

        autocomplete: function($element) {
            var wordUnderCaret, caretPosition, normalized, wordPosition;
            var _this = this;

            return {
                source: function(value, collback) {
                    caretPosition = $element[0].selectionStart;

                    normalized = _this.getNormalized(value, _this.opsRegEx, caretPosition);

                    var separatorsPositions = _.compact(value.split('').map(function(char, i) {
                        return (/^\s$/.test(char)) ? i + 1 : null;
                    }));

                    wordPosition = (function(arr, position) {
                        var index = 0;
                        var result = {
                            start: 0,
                            end: position
                        };

                        if (arr.length) {
                            while (arr[index] < position) {
                                index++;
                            }

                            var isSpace = arr[index] === position;

                            result = {
                                start: isSpace ? null : arr[index - 1] || 0,
                                end: isSpace ? null : arr[index] - 1 || position,
                                index: index,
                                spaces: arr
                            };
                        }

                        return result;
                    })(separatorsPositions, caretPosition);

                    wordUnderCaret = _this.getStringPart(normalized.string, wordPosition.start, wordPosition.end);

                    var suggests = _this.getSuggestList(normalized, wordPosition, wordUnderCaret);
                    console.log(wordUnderCaret, suggests);

                    collback(suggests || []);
                },
                matcher: function(item) {
                    return item.indexOf(wordUnderCaret) !== -1 || _this.hasLastSpace(wordUnderCaret);
                },
                updater: function(item) {
                    console.log(wordPosition);

                    var cutBefore = _.isNull(wordPosition.start) ? wordPosition.spaces[wordPosition.index] : wordPosition.start;
                    var cutAfter = _.isNull(wordPosition.end) ? wordPosition.spaces[wordPosition.index] : wordPosition.end;

                    var queryPartBefore = _this.getStringPart(this.query, 0, cutBefore);
                    var queryPartAfter = _this.getStringPart(this.query, cutAfter);

                    setTimeout(function() {
                        $element[0].selectionStart = $element[0].selectionEnd = cutBefore + item.length;
                    }, 10);

                    console.log(queryPartBefore, item, queryPartAfter, wordPosition);


                    return queryPartBefore + item + queryPartAfter;
                }
            };
        },

        hasLastSpace: function(string) {
            return string.substr(string.length - 1, 1) === ' ';
        },

        getSuggestList: function(normalized, wordPosition, wordUnderCaret) {
            var expressionMatch = wordUnderCaret.match(this.opsRegEx);
            var hasExpression = !_.isNull(expressionMatch);
            var expressionSplit = hasExpression ? wordUnderCaret.split(expressionMatch[0]) : [];
            var hasDataCase = (hasExpression && _.contains(this.dataWordCases, expressionSplit[0])) || _.contains(this.dataWordCases, wordUnderCaret.trim());
            var hasDataValue = !!(expressionSplit[1] && expressionSplit[1].length);
            var hasLastSpace = this.hasLastSpace(wordUnderCaret);
            var words = this.splitString(normalized.string, ' ').arr;
            var groups = this.getGroups(words);
            var logicLast = groups.logicWords.length && _.last(groups.logicWords) === _.last(words);


            console.log(logicLast, groups.logicWords.length , _.last(groups.logicWords) , _.last(words));


            if (hasDataCase && !logicLast) {
                if (hasExpression) {
                    if (hasDataValue && hasLastSpace) {
                        return this.logicWordCases;
                    }
                } else if (hasLastSpace) {
                    return this.operationsCases;
                }
            } else {
                return this.getFilteredSuggests(wordUnderCaret, this.dataWordCases);
            }
        },

        getNormalized: function(value, regex, caretPosition) {
            var result = {string: this.applyRegexp(this.clearSpaces(value), regex)};

            if (_.isNumber(caretPosition)) {
                var beforeCaretValue = this.applyRegexp(this.clearSpaces(this.getStringPart(value, 0, caretPosition)), regex);

                return _.extend(result, {caretPosition: beforeCaretValue.length});
            }

            return result;
        },
        clearSpaces: function(string) {
            return string.replace(/\s+/g, ' ');
        },
        applyRegexp: function(string, regex) {
            return string.replace(regex, '$1');
        },

        getFilteredSuggests: function(word, ref) {
            if (!word) {
                return [];
            }

            var arr = _.filter(ref, function(item) {
                return item.indexOf(word) === 0;
            });

            return arr.length > 1 || arr[0] !== word ? arr : [];
        },

        getPathsArray: function(src, baseName, baseArr) {
            var self = this;
            var arr = [baseName];

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
            var length = _.isNumber(endPos) ? endPos - startPos : undefined;

            return _.isNumber(startPos) ? string.substr(startPos, length) : string;
        },

        getRegexp: function(opsArr) {
            var escapedOps = (function(ops) {
                if (ops && ops.length) {
                    return ops.map(function(item) {
                        return '\\' + item.split('').join('\\');
                    });
                }

                return null;
            })(opsArr);

            return escapedOps ? new RegExp('\\s*((' + escapedOps.join(')|(') + '))\\s*', 'gi') : null;
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
