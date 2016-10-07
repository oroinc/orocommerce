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
                pricelist: {},
                products: {
                    type: 'array',
                    entity: 'product'
                }
            },
            operations: {
                math: ['+', '-', '%', '*', '/'],
                bool: ['and', 'or'],
                compare: ['>', '<', '=', '!='],
                inclusion: ['in', 'not in']
            },
            allowedOperations: ['math', 'bool', 'compare', 'inclusion']
        },

        /**
         *
         * @property {jQuery}
         */
        $element: null,

        /**
         *
         * @property {Object}
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
         * @property {Object}
         */
        error: {},

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$element = this.options._sourceElement;

            this.allowedCompare = _.contains(this.options.allowedOperations, 'compare');
            this.allowedInclusion = _.contains(this.options.allowedOperations, 'inclusion');
            this.allowedMath = _.contains(this.options.allowedOperations, 'math');
            this.allowedBool = _.contains(this.options.allowedOperations, 'bool');

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

            if (!this.allowedBool && groups.bool.length) {
                return false;
            }

            var logicIsValid = _.last(groups.bool) !== _.last(words) && _.every(groups.bool, function(item) {
                    return _this.contains(_this.options.operations.bool, item);
                });

            var dataWordsAreValid = _.every(groups.expr, function(item, i) {
                return _this.checkWord(item);
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

            _.each(value, function(char) {
                if (nestingLevel >= 0) {
                    if (char === '[') {
                        nestingLevel++;
                    }
                    if (char === ']') {
                        nestingLevel--;
                    }
                }
            });

            this.error.brackets = nestingLevel !== 0 ? 'Wrong balance of brackets' : '';

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
         * @param term
         * @returns {*}
         */
        checkTerm: function(term) {
            var isCorrect = term ? this.contains(this.dataWordCases, term) : false;

            this.error.term = !isCorrect ? 'Wrong term is \'' + term + '\'' : '';

            return isCorrect;
        },

        /**
         *
         * @param string
         * @param splitter
         * @returns {*}
         */
        checkCompare: function(string, splitter) {
            var matchSplit = this.getTermAndExpr(string, splitter),
                pathValue = this.getValueByPath(this.options.data, matchSplit.term);

            return this.checkTerm(matchSplit.term) && this.checkExpression(matchSplit.expr, pathValue);
        },

        /**
         *
         * @param string
         * @param match
         * @returns {*}
         */
        checkInclusion: function(string, match) {
            var matchSplit = this.getTermAndExpr(string, match);

            return this.checkTerm(matchSplit.term) && this.getArray(matchSplit.expr).is && this.checkValues(matchSplit.expr.split(','));
        },

        checkValues: function(arr) {
            var _this = this;

            return _.every(arr, function(item) {
                var num = Number(item);
                return (_.isNumber(num) && !_.isNaN(num)) || _.contains(_this.dataWordCases, item);
            })
        },
        /**
         *
         * @param string
         * @param match
         * @returns {{term: *, expr: *}}
         */
        getTermAndExpr: function(string, match) {
            var matchSplit = this.splitString(string, match);

            return {
                term: !_.isEmpty(matchSplit[0]) ? matchSplit[0].replace(/\[(.*?)\]/g, '') : null,
                expr: !_.isEmpty(matchSplit[1]) ? matchSplit[1].replace(/(^\[)|(\]$)/g, '').replace(/\[(.*?)\]/g, '') : null
            };
        },

        getArray: function(arr) {
            return {
                is: !_.isEmpty(arr) && !_.isEmpty(_.last(arr)),
                array: arr
            };
        },

        /**
         *
         * @param expr
         * @param keyData
         * @returns {*}
         */
        checkExpression: function(expr, keyData) {
            if (!expr) {
                return false;
            }

            var _this = this;

            var mathMatch = expr.match(this.opsRegEx['math']);

            var hasArray = this.getArray(expr);
            var isArray = hasArray.is && !_.isEmpty(_.last(hasArray.array)) && !mathMatch;

            if (!_.isEmpty(keyData) && keyData !== 'any' && !hasArray) {
                var isAssigned = this.contains(keyData, expr);
            } else if (mathMatch) {
                var values = _.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' ');

                var isExpression = !hasBool(values) && !_.contains(values, '') &&
                    this.checkValues(values);
            }

            var isCorrect = isExpression || isArray || isAssigned;

            this.error.expr = !isCorrect ? 'Wrong expression is \'' + expr + '\'' : '';

            return isCorrect;

            function hasBool(data) {
                return _.every(_.flatten([data]), function(value) {
                    return _.contains(_this.operationsCases.bool, value);
                });
            }
        },

        /**
         *
         * @param string
         * @returns {*}
         */
        checkWord: function(string) {
            var compareMatch = string.match(this.opsRegEx['compare']);
            var inclusionMatch = string.match(this.opsRegEx['inclusion']);

            var doCompare = compareMatch && compareMatch.length === 1 && this.allowedCompare;
            var doInclusion = inclusionMatch && inclusionMatch.length === 1 && this.allowedInclusion;
            var doMath = this.allowedMath && !this.allowedCompare && !this.allowedInclusion;

            if (doCompare) {
                var hasValidCompare = this.checkCompare(string, compareMatch);
            } else if (doInclusion) {
                var hasValidInclusion = this.checkInclusion(string, inclusionMatch);
            } else if (doMath) {
                var hasValidExpression = this.checkExpression(string);
            }

            return hasValidCompare || hasValidInclusion || hasValidExpression;
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
                case 'inclusion':
                    reString = '(\\s+|\\~*)(' + escapedOps.join('|') + ')(\\s+|\\~*)';
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

            string = string.replace(/(\(|\))/gi, ' ');

            string = string.replace(/\s*,\s*/g, ',');

            _.each(regex, function(re, name) {
                switch (name) {
                    case 'inclusion':
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

                    if (!_.contains(arr, baseName)) {
                        arr.push(baseName);
                    }

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
                expr: separateGroups(words, true),
                bool: separateGroups(words)
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
