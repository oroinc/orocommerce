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

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$element = this.options._sourceElement.find('input[type="text"]').eq(0);

            var self = this;

            this.$element.on('keyup paste', function() {
                var value = self.$element.val().trim().replace(/\s+/g, ' ');

                self.$element.toggleClass('error', !self.validate(value, self.options));
            });
            this.$element.on('keyup change paste', function() {
                self.autocomplete(self.$element, self.options);
            });
        },

        validate: function(value, options) {
            if (value === '') {
                return true;
            }

            var self = this;

            var opsRegEx = this.getRegexp(options.operations);

            var words = _.isRegExp(opsRegEx) ? value.replace(opsRegEx, '$1').split(' ') : [],
                groups = this.getGroups(words);

            var logicWordIsLast = _.last(groups.logic) === _.last(words),
                logicWordsAreValid = (function(arr, ref) {
                    return _.every(arr, function(item) {
                        return _.contains(ref, item);
                    });
                })(groups.logic, options.grouping),
                logicIsValid = !logicWordIsLast && logicWordsAreValid;

            var dataWordsAreValid = (function(arr, refs) {
                var isValid = logicIsValid;

                _.each(arr, function(item) {
                    if (isValid) {
                        var expressionMatch = item.match(opsRegEx);
                        var matchSplit = expressionMatch ? item.split(expressionMatch[0]) : [];

                        isValid = !_.isNull(expressionMatch) && matchSplit[1] !== '';

                        if (isValid) {
                            var path = (matchSplit[0] || item).split('.');
                            var currentRef = refs;

                            _.each(path, function(pathItem) {
                                if (isValid) {
                                    isValid = _.contains(_.isArray(currentRef) ? currentRef : _.keys(currentRef), pathItem);

                                    if (isValid && _.last(path) !== pathItem) {
                                        currentRef = self.getRef(refs, pathItem, currentRef);
                                    }
                                }
                            });
                        }
                    }
                });

                return isValid;
            })(groups.datas, options.data);

            return logicIsValid && dataWordsAreValid;
        },

        autocomplete: function($element, options) {
            var value = $element.val(),
                caretPosition = $element[0].selectionStart,
                separatorsPositions = (function(string) {
                    var arr = [0];

                    _.each(string, function(char, i) {
                        if (!isLetterBeforeCaret(char)) {
                            arr.push(i + 1);
                        }
                    });

                    return arr;
                })(value),
                nearestSeparatorPosition = (function(arr, position) {
                    var index = 0;

                    if (!arr.length) {
                        return index;
                    }


                    while (arr[index] < position) {
                        index++;
                    }

                    return arr[index - 1];

                })(separatorsPositions, caretPosition),
                wordBeforeCaret = getWordBeforeCaret(value, nearestSeparatorPosition, caretPosition);

            console.log(caretPosition, nearestSeparatorPosition, wordBeforeCaret);

            function isLetterBeforeCaret(char) {
                return /^[a-zA-Z]$/.test(char);
            }

            function getWordBeforeCaret(string, startPos, endPos) {
                return string.substr(startPos, endPos - startPos);
            }


        },

        getRegexp: function(opsArr) {
            var escapedOps = (function(ops) {
                var result = [];

                if (ops && ops.length) {
                    _.each(ops, function(item) {
                        result.push('\\' + item.split('').join('\\'));
                    });
                }

                return result;
            })(opsArr);

            return escapedOps && escapedOps.length ? new RegExp('\\s*((' + escapedOps.join(')|(') + '))\\s*', 'g') : null;
        },

        getGroups: function(words) {
            return {
                datas: separateGroups(words, true),
                logic: separateGroups(words)
            };

            function separateGroups(groups, isOdd) {
                return _.filter(groups, function(item, i) {
                    var modulo = i % 2;
                    return isOdd ? !modulo : modulo;
                });
            }
        },

        getRef: function(refs, pathItem, currentRef) {
            return _.constant(refs, pathItem) ? refs[pathItem] : currentRef[pathItem];
        }
    });

    return RuleEditorComponent;
});
