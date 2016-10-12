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
                pricelist: {
                    id: 'any',
                    name: 'any',
                    parent: 'any'
                },
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
         * @property {Object}
         */
        cases: {},

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

            this.cases.data = this.getStrings(options.data);

            _.each(this.options.allowedOperations, function(item) {
                _this.cases[item] = _this.getStrings(_this.options.operations[item]);
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

            this.initAutocomplete();
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

            var dataWordsAreValid = _.every(groups.expr, function(item) {
                return _this.checkWord(item).isValid;
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
                highlighter: function(item) {
                    var query = (_this.getWordUnderCaret(this.query) || this.query).replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&');

                    return item.replace(new RegExp('(' + query + ')', 'ig'), function($1, match) {
                        return '<strong>' + match + '</strong>'
                    });
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
         * @param query
         * @returns {*|String}
         */
        getWordUnderCaret: function(query) {
            var caretPosition = this.$element[0].selectionStart;
            var wordPosition = this.getWordPosition(query, caretPosition);

            return this.getStringPart(query, wordPosition.start, wordPosition.end);
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

            this.error.brackets = nestingLevel !== 0 ? 'Wrong balance of brackets' : undefined;

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

            return {
                array: this.getSuggestList(normalized, this.getWordUnderCaret(value)),
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
            var result;
            var _this = this;

            if (_.isEmpty(normalized.string)) {
                return this.cases.data;
            }

            var words = this.splitString(normalized.string, ' ');
            var wordsLength = words.length;

            var wordIs = checkWord(words[wordsLength - 1]);

            var isSpaceUnderCaret = _.isNull(wordUnderCaret);
            var isCompleteWord = wordIs.isInclusion || wordIs.isCompare;

            if (isSpaceUnderCaret) {
                if (isCompleteWord) {
                    result = this.cases.bool;
                } else if (wordIs.hasTerm) {
                    result = getCases(wordUnderCaret, this.cases.compare, this.cases.inclusion);
                }
            } else {
                result = getCases(wordUnderCaret);
            }

            return result;

            /**
             *
             * @param word
             * @returns {{isCompare: (*|boolean), isInclusion: (*|boolean), hasTerm: *}}
             */
            function checkWord(word) {
                var checkIt = _this.checkWord(word),
                    isDataTerm = _this.checkTerm(word, _this.cases.data);

                return {
                    isCompare: checkIt.hasCompare && checkIt.isValid,
                    isInclusion: checkIt.hasInclusion && checkIt.isValid,
                    hasTerm: isDataTerm
                };
            }

            function getCases(word) {
                var base = Array.prototype.slice.call(arguments, 1),
                    cases = _.union(_.flatten(!_.isEmpty(base) ? base : _.values(_this.cases)));

                return _this.getFilteredSuggests(cases, word);
            }
        },

        /**
         *
         * @param term
         * @param base
         * @returns {*}
         */
        checkTerm: function(term, base) {
            var validBrackets = true;
            var bracketsMatch = term.match(/\[(.*?)\]/g);

            if (bracketsMatch) {
                validBrackets = bracketsMatch.length === 1 ? this.checkArray(this.replaceBetween(bracketsMatch[0], '[]').split(',')) : false;
            }

            var isCorrect = term && validBrackets ? this.contains(base, this.replaceBetween(term, '[]', 'wipe')) : false;

            this.error.term = !isCorrect ? 'Part \'' + term + '\'' + ' is wrong' : undefined;

            return isCorrect;
        },

        /**
         *
         * @param string
         * @param match
         * @returns {*}
         */
        checkCompare: function(string, match) {
            var matchSplit = this.splitTermAndExpr(string, match),
                pathValue = this.getValueByPath(this.options.data, matchSplit.term);

            return this.checkTerm(matchSplit.term, this.cases.data) && this.checkExpression(matchSplit.expr, pathValue);
        },

        /**
         *
         * @param string
         * @param match
         * @returns {*}
         */
        checkInclusion: function(string, match) {
            var matchSplit = this.splitTermAndExpr(string, match),
                expr = this.replaceBetween(matchSplit.expr, '[]', 'trim');

            return this.checkTerm(matchSplit.term, this.cases.data) && !_.isEmpty(matchSplit.expr) && (this.checkArray(expr.split(',')) || this.checkTerm(expr, this.cases.data));
        },

        /**
         *
         * @param arr
         * @returns {boolean}
         */
        checkArray: function(arr) {
            if (_.isEmpty(arr)) {
                return false;
            }

            var _this = this;

            return _.every(arr, function(item) {
                return _this.checkValue(item);
            });

        },

        /**
         *
         * @param value
         * @returns {boolean}
         */
        checkValue: function(value) {
            var num = Number(value),
                isCorrect = !_.isEmpty(value) && ((_.isNumber(num) && !_.isNaN(num)) || _.contains(this.cases.data, value));

            this.error.value = !isCorrect ? 'Wrong value is \'' + value + '\'' : undefined;

            return isCorrect;
        },

        /**
         *
         * @param string
         * @param match
         * @returns {{term: *, expr: *}}
         */
        splitTermAndExpr: function(string, match) {
            var matchSplit = this.splitString(string, match);

            return {
                term: !_.isEmpty(matchSplit[0]) ? matchSplit[0] : null,
                expr: !_.isEmpty(matchSplit[1]) ? matchSplit[1] : null
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

            expr = this.replaceBetween(this.replaceBetween(expr, '[]', 'trim'), '[]', 'wipe');

            var _this = this;

            if (this.checkValue(expr)) {
                return true;
            }

            if (this.checkArray(expr.split(','))) {
                return true;
            }

            var mathMatch = expr.match(this.opsRegEx['math']);

            if (mathMatch) {
                var values = _.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' ');

                return !_.contains(values, '') && !hasBool(values) && this.checkArray(values);
            } else {
                return this.contains(keyData, expr);
            }

            function hasBool(data) {
                return _.every(_.flatten([data]), function(value) {
                    return _.contains(_this.cases.bool, value);
                });
            }
        },

        /**
         *
         * @param string
         * @returns {{compare: (*|boolean), inclusion: (*|boolean), math: (*|boolean), match: undefined}}
         */
        getOperationType: function(string) {
            var compareMatch = string.match(this.opsRegEx['compare']),
                inclusionMatch = string.match(this.opsRegEx['inclusion']),
                mathMatch = string.match(this.opsRegEx['math']),
                doCompare = compareMatch && compareMatch.length === 1 && this.allowedCompare,
                doInclusion = inclusionMatch && inclusionMatch.length === 1 && this.allowedInclusion,
                doMath = this.allowedMath && !this.allowedCompare && !this.allowedInclusion,
                match = doCompare ? compareMatch : (doInclusion ? inclusionMatch : (doMath ? mathMatch : undefined));

            return {
                compare: doCompare,
                inclusion: doInclusion,
                math: doMath,
                match: match
            };
        },

        /**
         *
         * @param string
         * @returns {*}
         */
        checkWord: function(string) {
            var operationType = this.getOperationType(string);

            if (operationType.compare) {
                var isCompare = this.checkCompare(string, operationType.match);
            } else if (operationType.inclusion) {
                var isInclusion = this.checkInclusion(string, operationType.match);
            } else if (operationType.math) {
                var isExpression = this.checkExpression(string);
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
         *
         * @param opsArr
         * @param name
         * @returns {RegExp}
         */
        getRegexp: function(opsArr, name) {
            if (_.isEmpty(opsArr)) {
                return null;
            }

            var reString,
                escapedOps = opsArr.map(function(item) {
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
            var hasCutPosition = !_.isUndefined(caretPosition),
                string = hasCutPosition ? this.getStringPart(value, 0, caretPosition) : value;

            string = this.replaceBetween(string, '()', ' ');

            string = string.replace(/\s*\]/g, ']').replace(/\[\s*/g, '[');

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
                var lowercaseItem = item.toLowerCase(),
                    lowercaseWord = word.toLowerCase();

                return lowercaseItem.indexOf(lowercaseWord) === 0 && lowercaseItem !== lowercaseWord;
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
            var _this = this,
                arr = [];

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

        /**
         *
         * @param arr
         * @param value
         * @returns {Boolean}
         */
        contains: function(arr, value) {
            if (!_.isArray(arr)) {
                return false;
            }

            return _.some(arr, function(item) {
                return value.toLowerCase() === item.toLowerCase();
            });
        },

        /**
         *
         * @param obj
         * @param path
         * @returns {*}
         */
        getValueByPath: function(obj, path) {
            var result = obj;

            _.each(path.split('.'), function(node) {
                if (result[node]) {
                    result = result[node];
                }
            });

            return result;
        },

        /**
         *
         * @param input
         * @param brackets
         * @param type
         * @param replace
         * @returns {String}
         */
        replaceBetween: function(input, brackets, type, replace) {
            var _this = this;

            if (_.isArray(input)) {
                _.each(input, function(string, i) {
                    input[i] = _this.replaceBetween(string, brackets, type, replace);
                });
            } else if (_.isArray(brackets)) {
                _.each(brackets, function(item) {
                    input = _this.replaceBetween(input, item, type, replace);
                });
            } else {
                input = input ? _replace(input, brackets, type, replace).trim() : input;
            }

            return input;

            function _replace(string, brackets, type, replace) {
                switch (type) {
                    case 'trim':
                        return string.replace(new RegExp('^' + '\\' + brackets.split('').join('|\\') + '$', 'g'), replace || '');
                        break;
                    case 'wipe':
                        return string.replace(new RegExp('\\' + brackets.split('').join('(.*?)\\'), 'g'), replace || '');
                        break;
                    default:
                        return string.replace(new RegExp('\\' + brackets.split('').join('|\\'), 'g'), replace || '');
                }
            }
        }
    });

    return RuleEditorComponent;
});
