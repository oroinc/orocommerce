define(function(require) {
    'use strict';

    var RuleEditorComponent;
    var ViewComponent = require('oroui/js/app/components/view-component');
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
         * @param options
         */
        initialize: function(options) {
            this.cases = {}; // all string cases based on options.data
            this.error = {}; // reserved for error messages
            this.opsRegEx = {}; // regexp for operations
            this.allowed = {}; // allowed operations based on current input options

            this.options = _.defaults(options || {}, this.options);

            _.each(this.options.allowedOperations, function(name) {
                this.allowed[name] = true;
            }, this);

            this.cases.data = this.getStrings(options.data);

            _.each(this.options.allowedOperations, function(item) {
                this.cases[item] = this.getStrings(this.options.operations[item]);
                this.opsRegEx[item] = this.getRegexp(this.options.operations[item], item);
            }, this);

            options.view = options.view || this.view;
            options.component = this;
            return RuleEditorComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * Returns boolean of string validation.
         *
         * @param {String} expression
         * @returns {Boolean}
         */
        isValid: function(expression) {
            if (_.isEmpty(expression)) {
                return true;
            }

            if (!this.validBrackets(expression)) {
                return false;
            }

            var _this = this;

            var normalized = this.getNormalized(expression);
            var words = this.splitString(normalized.string, ' ');
            var groups = this.getGroups(words);

            if (!this.allowed.bool && groups.bool.length) {
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
         * Returns word on cursor position.
         *
         * @param {String} expression
         * @param {Number} caretPosition
         * @returns {*|String}
         */
        getWordUnderCaret: function(expression, caretPosition) {
            var wordPosition = this.getWordPosition(expression, caretPosition);

            return this.getStringPart(expression, wordPosition.start, wordPosition.end);
        },

        /**
         * Returns boolean of brackets validation.
         *
         * @param {String} expression
         * @returns {Boolean}
         */
        validBrackets: function(expression) {
            var breakLoop = false;
            var expected = [];
            var bracketsOnly = expression.replace(/[^\[\](){}]/g, '');

            _.each(bracketsOnly, function(char) {
                if (!breakLoop) {
                    setOpen(char, '(', ')');
                    setOpen(char, '[', ']');
                    checkClose(char, ')');
                    checkClose(char, ']');
                }
            });

            if (breakLoop || !_.isEmpty(expected)) {
                this.error.brackets = 'Wrong brackets balance in \'' + expression + '\'';
                return false;
            }

            return true;

            /**
             * Adds expected 'close' bracket.
             *
             * @param symbol
             * @param char
             * @param closeExpect
             */

            function setOpen(symbol, char, closeExpect) {
                if (char === symbol) {
                    expected.push(closeExpect);
                }
            }

            /**
             * Check expected 'close' bracket and set 'unloop' if not.
             *
             * @param symbol
             * @param char
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
         * @param {String} expression
         * @param item
         * @param position
         * @returns {*}
         */
        getUpdateValue: function(expression, item, position) {
            var cutBefore = _.isNull(position.start) ? position.spaces[position.index] : position.start;
            var cutAfter = _.isNull(position.end) ? position.spaces[position.index] : position.end;

            var queryPartBefore = this.getStringPart(expression, 0, cutBefore);
            var queryPartAfter = this.getStringPart(expression, cutAfter);

            var doSpaceOffset = !queryPartAfter.trim();

            return {
                value: queryPartBefore + item + (doSpaceOffset ? ' ' : queryPartAfter),
                position: cutBefore + item.length + (doSpaceOffset ? 1 : 0)
            };
        },

        /**
         * Returns object with suggested list and word position.
         *
         * @param {String} expression
         * @param {Number} caretPosition
         * @returns {{array: (*|Array), position: (*|{start: *, end: *, index: number, spaces: array})}}
         */
        getAutocompleteSource: function(expression, caretPosition) {
            var normalized = this.getNormalized(expression, caretPosition);
            var wordPosition = this.getWordPosition(expression, caretPosition);

            return {
                array: this.getSuggestList(normalized, this.getWordUnderCaret(expression, caretPosition)),
                position: wordPosition
            };
        },

        /**
         * Getting the word's start position.
         *
         * @param {String} expression
         * @param {Number} caretPosition
         * @returns {{start: number, end: *}}
         */
        getWordPosition: function(expression, caretPosition) {
            var index = 0;
            var result = {
                start: 0,
                end: caretPosition
            };
            var separatorsPositions = _.compact(expression.split('').map(function(char, i) {
                return (/^(\s|\(|\))$/.test(char)) ? i + 1 : null;
            }));

            if (separatorsPositions.length) {
                while (separatorsPositions[index] < caretPosition) {
                    index++;
                }

                var isSpace = separatorsPositions[index] === caretPosition;

                result = {
                    start: isSpace ? null : separatorsPositions[index - 1] || 0,
                    end: isSpace ? null : caretPosition,
                    index: index,
                    spaces: separatorsPositions
                };
            }

            return result;
        },

        /**
         * Getting a list of matching words.
         *
         * @param normalized
         * @param wordUnderCaret
         * @returns {Array}
         */
        getSuggestList: function(normalized, wordUnderCaret) {
            var result = [];
            var _this = this;

            if (_.isEmpty(normalized.string)) {
                return this.cases.data;
            }

            var words = this.splitString(normalized.string, ' ');
            var word = words[words.length - 1];

            var wordIs = checkWord(word);

            var isSpaceUnderCaret = _.isNull(wordUnderCaret);
            var isCompleteWord = wordIs.isInclusion || wordIs.isCompare;
            var pathValue = this.getValueByPath(this.options.data, word.replace(this.opsRegEx.compare, ''));

            if (isSpaceUnderCaret) {
                if (isCompleteWord) {
                    result = _.union(result, this.cases.bool);

                    if (this.allowed.math) {
                        result = _.union(result, getCases(wordUnderCaret, this.cases.math));
                    }
                } else if (wordIs.isBool) {
                    result = this.cases.data;
                } else if (wordIs.hasTerm || wordIs.hasValue || wordIs.notOps) {
                    if (this.allowed.compare) {
                        result = _.union(result, getCases(wordUnderCaret, this.cases.compare));
                    }
                    if (this.allowed.inclusion) {
                        result = _.union(result, getCases(wordUnderCaret, this.cases.inclusion));
                    }
                    if (!this.allowed.inclusion && !this.allowed.compare) {
                        result = this.cases.math;
                    }
                } else {
                    return pathValue && pathValue !== 'any' ? pathValue : this.cases.data;
                }
            } else {
                result = getCases(wordUnderCaret);
            }

            return result;

            /**
             * Getting of the word's correspondences.
             *
             * @param word
             * @returns {{isCompare: (*|boolean), isInclusion: (*|boolean), hasTerm: *}}
             */
            function checkWord(word) {
                var checkIt = _this.checkWord(word),
                    lastChar = word[word.length - 1];

                return {
                    hasCompare: checkIt.hasCompare,
                    hasInclusion: checkIt.hasInclusion,
                    isCompare: checkIt.hasCompare && checkIt.isValid,
                    isInclusion: checkIt.hasInclusion && checkIt.isValid,
                    hasTerm: _this.checkTerm(word, _this.cases.data),
                    hasValue: _this.checkValue(word),
                    isBool: _.contains(_this.cases.bool, word),
                    isMath: _.contains(_this.cases.math, lastChar),
                    notOps: /[a-zA-Z\[\]()]/g.test(lastChar)
                };
            }

            /**
             * Getting list of cases from all subcases.
             *
             * @param word
             * @returns {*}
             */

            function getCases(word) {
                var base = Array.prototype.slice.call(arguments, 1),
                    cases = _.union(_.flatten(_.isEmpty(base) ? _.values(_this.cases) : base));

                return _this.getFilteredSuggests(cases, word);
            }
        },

        /**
         * Checking of accessory word to terms.
         *
         * @param term
         * @param base
         * @returns {*}
         */
        checkTerm: function(term, base) {
            if (!term){
                return false;
            }

            if (!this.contains(base, this.replaceWraps(term, '[]', 'wipe'))){
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
         * Returns boolean whether string is a expression of inclusion.
         *
         * @param string
         * @param match
         * @returns {*}
         */
        checkInclusion: function(string, match) {
            var matchSplit = this.splitTermAndExpr(string, match),
                expr = this.replaceWraps(matchSplit.expr, '[]', 'trim');

            return this.checkTerm(matchSplit.term, this.cases.data) &&
                !_.isEmpty(matchSplit.expr) &&
                (this.checkArray(matchSplit.expr) || this.checkTerm(expr, this.cases.data));
        },

        /**
         * Returns boolean whether string is a array, non empty array, and has valid values.
         *
         * @param array
         * @returns {boolean}
         */
        checkArray: function(array) {
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
             * @param string
             * @param re
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
             * @param array
             * @returns {boolean}
             */
            function checkValues(array) {
                return _.every(array, function(value) {
                    if (_.isEmpty(value)) {
                        _this.error.array = 'One of \'' + array + '\' array items is empty';
                        return false;
                    }

                    return _this.checkValue(value);
                });
            }
        },

        /**
         * Returns boolean whether value is valid: is number, is not NaN, or is a term.
         *
         * @param value
         * @returns {boolean}
         */
        checkValue: function(value) {
            if (_.isEmpty(value)) {
                return false;
            }

            var num = Number(value),
                isNumber = _.isNumber(num) && !_.isNaN(num);

            return isNumber || this.checkTerm(value, this.cases.data);
        },

        /**
         * Returns pair of term and expression.
         *
         * @param string
         * @param match
         * @returns {{term: *, expr: *}}
         */
        splitTermAndExpr: function(string, match) {
            var matchSplit = this.splitString(string, match);

            return {
                term: _.isEmpty(matchSplit[0]) ? null : matchSplit[0],
                expr: _.isEmpty(matchSplit[1]) ? null : matchSplit[1]
            };
        },

        /**
         * Returns boolean whether expression is: valid value, or valid array, or belongs to keyData array.
         *
         * @param expr
         * @param keyData
         * @returns {*}
         */
        checkExpression: function(expr, keyData) {
            if (!expr) {
                return false;
            }

            if (this.checkValue(expr)) {
                return true;
            }

            if (this.checkArray(expr)) {
                return true;
            }

            if (this.contains(keyData, expr)) {
                return true;
            }

            var mathMatch = expr.match(this.opsRegEx.math);

            if (mathMatch) {
                return this.checkArray(_.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' '));
            }
        },

        /**
         * Returns boolean for operations type used in string and the matched string.
         *
         * @param string
         * @returns {{compare: (*|boolean), inclusion: (*|boolean), math: (*|boolean), match: undefined}}
         */
        getOperationType: function(string) {
            var compareMatch = string.match(this.opsRegEx.compare);
            var inclusionMatch = string.match(this.opsRegEx.inclusion);
            var mathMatch = string.match(this.opsRegEx.math);
            var doCompare = compareMatch && compareMatch.length === 1 && this.allowed.compare;
            var doInclusion = inclusionMatch && inclusionMatch.length === 1 && this.allowed.inclusion;
            var doMath = this.allowed.math && !this.allowed.compare && !this.allowed.inclusion;
            var match;

            if (doCompare) {
                match = compareMatch;
            } else if (doInclusion) {
                match = inclusionMatch;
            } else if (doMath) {
                match = mathMatch;
            }

            return {
                compare: doCompare,
                inclusion: doInclusion,
                math: doMath,
                match: match
            };
        },

        /**
         * Returns the word's state.
         *
         * @param string
         * @returns {*}
         */
        checkWord: function(string) {
            var operationType = this.getOperationType(string);
            var isValid = false;

            if (operationType.compare) {
                isValid = this.checkCompare(string, operationType.match);
            } else if (operationType.inclusion) {
                isValid = this.checkInclusion(string, operationType.match);
            } else if (operationType.math) {
                isValid = this.checkExpression(string);
            }

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
         * Returns RegExp object for operations array.
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
                    if (item.match(/\s|\w/gi)) {
                        return item.replace(/\s+/gi, '\\s?');
                    } else {
                        return '\\' + item.split('').join('\\');
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
         * Returns normalized string and corrected cursor position.
         *
         * @param {String} expression
         * @param caretPosition
         * @returns {{string: string, position: number}}
         */
        getNormalized: function(expression, caretPosition) {
            var hasCutPosition = !_.isUndefined(caretPosition),
                string = hasCutPosition ? this.getStringPart(expression, 0, caretPosition) : expression;

            string = this.replaceWraps(string, '()', ' ');

            string = string.replace(/\[\s*/g, '[').replace(/\s*\]/g, ']');

            string = string.replace(/\s*,\s*/g, ',');

            _.each(this.opsRegEx, function(re, name) {
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
         * Returns list filtered by the word.
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
         * Returns list of cases based on options.date structure.
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
                        arr = _.union(arr, _this.getStrings(baseData[name], subName, baseData || src));
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
         */
        getStringPart: function(string, startPos, endPos) {
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
         */
        splitString: function(string, splitter) {
            return _.compact(string.split(splitter));
        },

        /**
         * Returns groups of expressions (even) and booleans (odd) from array of words.
         *
         * @param string
         * @returns {{expr: *, bool: *}}
         */
        getGroups: function(string) {
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
         * Returns value of object key requested by path.
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
         * Returns cleared of brackets. Types: trim, wipe, remove (by default).
         *
         * @param input {String}
         * @param brackets {String}
         * @param type {String}
         * @param replace {String}
         * @returns {String}
         */
        replaceWraps: function(input, brackets, type, replace) {
            var _this = this;

            if (_.isArray(input)) {
                _.each(input, function(string, i) {
                    input[i] = _this.replaceWraps(string, brackets, type, replace);
                });
            } else if (_.isArray(brackets)) {
                _.each(brackets, function(item) {
                    input = _this.replaceWraps(input, item, type, replace);
                });
            } else {
                input = input ? _replace(input, brackets, type, replace).trim() : input;
            }

            return input;

            function _replace(string, brackets, type, replace) {
                var split = brackets.split('');
                if (type === 'trim') {
                    return string.replace(new RegExp('^' + '\\' + split.join('(.*?)\\') + '$', 'g'), _.isUndefined(replace) ? '$1' : replace + '$1' + replace);
                } else if (type === 'wipe') {
                    return string.replace(new RegExp('\\' + split.join('(.*?)\\'), 'g'), replace || '');
                } else {
                    return string.replace(new RegExp('\\' + split.join('|\\'), 'g'), replace || '');
                }
            }
        }
    });

    return RuleEditorComponent;
});
