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
                compare: ['==', '!=', '>', '<', '<=', '>=', 'like'],
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
         * @param options
         */
        initialize: function(options) {
            var _this = this;

            this.cases = {};
            this.error = {};
            this.opsRegEx = {};
            this.allowed = {};

            this.options = _.defaults(options || {}, this.options);
            this.$element = this.options._sourceElement;

            _.each(this.options.allowedOperations, function(name) {
                _this.allowed[name] = true;
            });

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
                }, 10);
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

            if (!this.validBrackets(value)) {
                return false;
            }

            var _this = this;

            var normalized = this.getNormalized(value, this.opsRegEx);
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
         *
         */
        initAutocomplete: function() {
            var _context;
            var _position;
            var _this = this;

            _context = this.$element.typeahead({
                minLength: 0,
                items: 10,
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
        validBrackets: function(value) {
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
                return false
            }

            return true;

            function setOpen(symbol, char, closeExpect) {
                if (char === symbol) {
                    expected.push(closeExpect);
                }
            }

            function checkClose(symbol, char) {
                if (char === symbol) {
                    breakLoop = expected.pop() !== char;
                }
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

            var doSpaceOffset = !queryPartAfter.trim();

            setTimeout(function() {
                _this.$element[0].selectionStart = _this.$element[0].selectionEnd = cutBefore + item.length + (doSpaceOffset ? 1 : 0);
                _this.$element.trigger('keyup');
            }, 10);


            return queryPartBefore + item + (doSpaceOffset ? ' ' : queryPartAfter);
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
                expr = this.replaceWraps(matchSplit.expr, '[]', 'trim');

            return this.checkTerm(matchSplit.term, this.cases.data) && !_.isEmpty(matchSplit.expr) && (this.checkArray(matchSplit.expr) || this.checkTerm(expr, this.cases.data));
        },

        /**
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

            function getMatches(string, re) {
                var match,
                    matches = [];

                while ((match = re.exec(string)) !== null) {
                    matches.push(match[2]);
                }

                return matches;
            }

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
         *
         * @param value
         * @returns {boolean}
         */
        checkValue: function(value) {
            if (_.isEmpty(value)) {
                return false
            }

            var num = Number(value),
                isNumber = _.isNumber(num) && !_.isNaN(num);

            return isNumber || this.checkTerm(value, this.cases.data);
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

            if (this.checkValue(expr)) {
                return true;
            }

            if (this.checkArray(expr)) {
                return true;
            }

            if (this.contains(keyData, expr)) {
                return true;
            }

            var mathMatch = expr.match(this.opsRegEx['math']);

            if (mathMatch) {
                return this.checkArray(_.reduce(mathMatch, function(values, match) {
                    return values.replace(match, ' ');
                }, expr).split(' '));
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

            string = this.replaceWraps(string, '()', ' ');

            string = string.replace(/\[\s*/g, '[').replace(/\s*\]/g, ']');

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

            return _.isNumber(startPos) ? String(string).substr(startPos, length) : string;
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

                switch (type) {
                    case 'trim':
                        return string.replace(new RegExp('^' + '\\' + split.join('(.*?)\\') + '$', 'g'), _.isUndefined(replace) ? '$1' : replace + '$1' + replace);
                        break;
                    case 'wipe':
                        return string.replace(new RegExp('\\' + split.join('(.*?)\\'), 'g'), replace || '');
                        break;
                    default:
                        return string.replace(new RegExp('\\' + split.join('|\\'), 'g'), replace || '');
                }
            }
        }
    });

    return RuleEditorComponent;
});
