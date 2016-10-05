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
        options: {
            data: {
                product: {
                    id: 'any',
                    name: 'any',
                    category: 'any',
                    status: ['ENABLED', 'DISABLED']
                },
                category: {
                    id: 'any',
                    name: 'any',
                    parent: 'any'
                },
                account: {
                    id: 'any',
                    name: 'any',
                    role: 'any'
                },
                products: {
                    type: 'array',
                    entity: 'product'
                }
            },
            operations: {
                math: ['+', '-', '%', '*', '/'],
                bool: ['and', 'or'],
                compare: ['>', '<', '=', '!=', 'in', 'not in']
            },
            allowedOperations: ['math', 'bool', 'compare']
        },

        /**
         *
         * @property {jQuery}
         */
        $element: null,

        /**
         *
         * @property {Array}
         */
        opsRegEx: {},

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
            this.$element = this.options._sourceElement;

            var _this = this;

            this.dataWordCases = this.getStrings(options.data);
            this.logicWordCases = this.getStrings(options.grouping);

            _.each(this.options.allowedOperations, function(item) {
                _this.operationsCases[item] = _this.getStrings(_this.options.operations[item]);
                _this.opsRegEx[item] = _this.getRegexp(_this.options.operations[item], item);
            });

            this.$element.on('keyup paste blur', function(e) {
                var $el = $(e.target);

                setTimeout(function() {
                    var isValid = _this.isValid($el.val());

                    $el.toggleClass('error', !isValid);
                    $el.parent().toggleClass('validation-error', !isValid);

                });
            });

            // this.initAutocomplete();
        },

        /**
         *
         * @param value
         * @returns {Boolean}
         */
        isValid: function(value) {
            if (_.isEmpty(value)) {
                return true;
            }

            if (!this.hasValidBrackets(value)) {
                return false;
            }

            var _this = this;

            var normalized = this.getNormalized(value, this.opsRegEx);
            var words = this.splitString(normalized.string, ' ');
            var groups = this.getGroups(words);

            var logicIsValid = _.last(groups.logicWords) !== _.last(words) && _.every(groups.logicWords, function(item) {
                    return _this.contains(_this.options.operations.bool, item);
                });

            var dataWordsAreValid = _.every(groups.dataWords, function(item) {
                return _this.isDataExpression(item).isFull;
            });

            return logicIsValid && dataWordsAreValid;
        },

        /**
         *
         */
        initAutocomplete: function() {
            var _context;
            var _position;
            var _this = this;

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
                focus: function() {
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

            function clickHandler() {
                var _this = this;
                var _arguments = arguments;

                setTimeout(function() {
                    _this.keyup.apply(_this, _arguments);
                }, 10);
            }
        },

        /**
         *
         * @param value {String}
         * @returns {Boolean}
         */
        hasValidBrackets: function(value) {
            var nestingLevel = 0;

            _.each(value, function(char) {
                if (nestingLevel >= 0) {
                    if (char === '(') {
                        nestingLevel++;
                    }
                    if (char === ')') {
                        nestingLevel--;
                    }
                }
            });

            return nestingLevel === 0;
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
         * @param value
         * @param position
         * @returns {{start: number, end: *}}
         */
        getWordPosition: function(value, position) {
            var index = 0;
            var result = {
                start: 0,
                end: position
            };
            var separatorsPositions = _.compact(value.split('').map(function(char, i) {
                return (/^(\s|\(|\))$/.test(char)) ? i + 1 : null;
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
         * @param normalized
         * @param wordUnderCaret
         * @returns {Array}
         */
        getSuggestList: function(normalized, wordUnderCaret) {
            var result = [];
            var _this = this;

            if (_.isEmpty(normalized.string)) { // initial suggestion for empty normalized value
                return this.dataWordCases;
            }

            var words = this.splitString(normalized.string, ' ');
            var wordsLength = words.length;
            var groups = this.getGroups(words);

            var isCheckedWord = wordIs(words[wordsLength - 1]);

            if (!_.some(isCheckedWord, function(item) {
                    return _.isBoolean(item) && item;
                }) && words[wordsLength - 2]) {
                isCheckedWord = wordIs(words[wordsLength - 2]);
            }

            if (isCheckedWord.dataExpression) { // previous word is a complete data expression
                result = this.getFilteredSuggests(this.logicWordCases, wordUnderCaret);
            } else if (isCheckedWord.dataWord) { // previous word is a data expression word
                result = this.getFilteredSuggests(this.operationsCases, wordUnderCaret);
            } else if (isCheckedWord.operation) { // previous word is an operation (=, !=, etc.)
                result = this.getFilteredSuggests(isCheckedWord.hasValues, wordUnderCaret);
            } else if (isCheckedWord.logic || wordsLength === 1) {
                result = this.getFilteredSuggests(this.dataWordCases, wordUnderCaret);
            }

            return result;

            function wordIs(word) {
                var isDataExpression = _this.isDataExpression(word);

                return {
                    logic: !_.isEmpty(groups.logicWords) && _this.contains(_this.logicWordCases, word),
                    dataWord: !_.isEmpty(groups.dataWords) && _this.contains(_this.dataWordCases, word),
                    dataExpression: !_.isEmpty(groups.dataWords) && _this.isDataExpression(word).isFull,
                    operation: isDataExpression.hasExpression,
                    hasValues: isDataExpression.values

                };
            }
        },

        /**
         *
         * @param string
         * @returns {*}
         */
        isDataExpression: function(string) {
            var _this = this;

            var compareMatch = string.match(this.opsRegEx['compare']);

            if (_.isNull(compareMatch) || compareMatch.length > 1) {
                return false;
            }

            var matchSplit = compareMatch ? this.splitString(string, compareMatch[0]) : null;
            var isValidWord = this.contains(this.dataWordCases, matchSplit[0]);
            var pathValue = this.getValueByPath(this.options.data, matchSplit[0]);

            if (!_.isEmpty(matchSplit[1])) {
                var expressionValues = matchSplit[1].replace(this.opsRegEx['math'], ' ').split(' ');
                var valueIsNotBool = _.every(expressionValues, function(value) {
                    return !_.contains(_this.operationsCases.bool, value);
                });
                var isValidExpression = valueIsNotBool && pathValue === 'any' && !_.contains(expressionValues, '') && !_.isEmpty(_.last(expressionValues));

                var hasInCases = this.contains(pathValue, matchSplit[1]);

                var arrayValues = matchSplit[1] ? matchSplit[1].split(',') : [];
                var isValidArray = pathValue.type === 'array' && !_.isEmpty(arrayValues).length && !_.isEmpty(_.last(arrayValues));

                var isValidValue = isValidExpression || hasInCases || isValidArray;
            }

            var isFullValid = isValidWord && isValidValue;

            return {
                isFull: isFullValid,
                hasExpression: !_.isEmpty(compareMatch),
                values: pathValue !== 'any' ? pathValue : []
            };
        },

        /**
         *
         * @param opsArr
         * @param name
         * @returns {RegExp}
         */
        getRegexp: function(opsArr, name) {
            if (_.isEmpty(opsArr)) {
                return null;
            }

            var reString;

            var escapedOps = opsArr.map(function(item) {
                if (!item.match(/\s|\w/gi)) {
                    return '\\' + item.split('').join('\\');
                } else {
                    return item.replace(/\s+/gi, '\\s?');
                }
            });

            switch (name) {
                case 'compare':
                    reString = '(\\s|\\~)*(' + escapedOps.join('|') + ')(\\s|\\~)*';
                    break;
                case 'bool':
                    reString = '\\s+(' + escapedOps.join('|') + ')\\s+';
                    break;
                default:
                    reString = '\\s*(' + escapedOps.join('|') + ')\\s*';
            }

            return new RegExp(reString, 'gi');
        },

        /**
         *
         * @param value
         * @param regex
         * @param caretPosition
         * @returns {{string: string, position: number}}
         */
        getNormalized: function(value, regex, caretPosition) {
            var hasCutPosition = !_.isUndefined(caretPosition);
            var string = hasCutPosition ? this.getStringPart(value, 0, caretPosition) : value;

            string = string.replace(/\[(.*?)\]/g, '');
            string = string.replace(/(\(|\))/gi, ' ');

            string = string.replace(/\s*,\s*/g, ',');

            _.each(regex, function(re, name) {
                switch (name) {
                    case 'compare':
                        string = string.replace(re, function(match) {
                            return '~' + match.replace(/\s+/g, '') + '~';
                        });
                        break;
                    case 'bool':
                        string = string.replace(re, ' $1 ');
                        break;
                    default:
                        string = string.replace(re, '$1');
                }
            });

            string = string.replace(/\s+/g, ' ');
            string = string.trim();

            return {
                string: string,
                position: string.length
            };
        },

        /**
         *
         * @param list
         * @param word
         * @returns {*}
         */
        getFilteredSuggests: function(list, word) {
            if (_.isEmpty(word) || _.isEmpty(list)) {
                return list;
            }

            var arr = _.filter(list, function(item) {
                return item.toLowerCase().indexOf(word.toLowerCase()) === 0;
            });

            return arr.length > 1 || arr[0] !== word ? arr : [];
        },

        /**
         *
         * @param src
         * @param baseName
         * @param baseData
         * @returns {Array}
         */
        getStrings: function(src, baseName, baseData) {
            var _this = this;
            var arr = [];

            if (_.isArray(src) && !baseName) {
                arr = _.union(arr, src);
            } else {
                _.each(src, function(item, name) {
                    var subName = baseName ? baseName + '.' + name : name;

                    if (item.type === 'array') {
                        arr.push(subName);
                    } else if (_.isObject(item) && !_.isArray(item)) {
                        arr = _.union(arr, _this.getStrings(item, name, baseData || src));
                    } else if (!_.isUndefined(baseData) && _.isObject(baseData[name])) {
                        arr = _.union(arr, _this.getStrings(baseData[name], subName, baseData || src))
                    } else {
                        arr.push(subName);
                    }
                });
            }

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
         * @param string
         * @param splitter
         * @returns {Array}
         */
        splitString: function(string, splitter) {
            return _.compact(string.split(splitter));
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
        },
        contains: function(arr, value) {
            if (!_.isArray(arr)) {
                return false;
            }

            return _.some(arr, function(item) {
                return value.toLowerCase() === item.toLowerCase();
            });
        },
        getValueByPath: function(obj, path) {
            var result = obj;

            _.each(path.split('.'), function(node) {
                if (result[node]) {
                    result = result[node];
                }
            });

            return result;
        }
    });

    return RuleEditorComponent;
});
