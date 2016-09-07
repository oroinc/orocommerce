define(function(require) {
    'use strict';

    var RuleEditorComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');

    RuleEditorComponent = BaseComponent.extend({
        options: null,

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var self = this;
            var $input = this.options._sourceElement.find('input[type="text"]').eq(0);

            $input.on('keyup', function() {
                $input.toggleClass('error', !self.validate($(this).val().trim()));
            });
        },

        validate: function(value) {
            if (value === '') {
                return true;
            }

            var escapedOps = getEscapedOps(this.options.operations);
            var opsRegEx = new RegExp('\\s*((' + escapedOps.join(')|(') + '))\\s*', 'g');
            var words = escapedOps && escapedOps.length ? value.replace(opsRegEx, '$1').split(' ') : [];

            var groupingWords = separateGroups(words);
            var groupingAreValid = (function(arr, ref) {
                return _.every(arr, function(item) {
                    return _.contains(ref, item);
                });
            })(groupingWords, this.options.grouping);
            var lastIsGrouping = _.last(groupingWords) === _.last(words);

            var dataWords = separateGroups(words, true);
            var dataAreValid = (function(arr, ref) {
                var isValid = true;

                _.each(arr, function(item) {
                    if (isValid) {
                        var expressionMatch = item.match(opsRegEx);
                        var matchSplit = expressionMatch ? item.split(expressionMatch[0]) : [];

                        isValid = !_.isNull(expressionMatch) && matchSplit[1] !== '';

                        if (isValid) {
                            var path = (matchSplit[0] || item).split('.');
                            var subRef = ref;

                            _.each(path, function(pathItem) {
                                if (isValid) {
                                    isValid = _.contains(_.isArray(subRef) ? subRef : _.keys(subRef), pathItem);

                                    if (isValid && _.last(path) !== pathItem) {
                                        subRef = _.constant(ref, pathItem) ? ref[pathItem] : subRef[pathItem];
                                    }
                                }
                            });
                        }
                    }
                });

                return isValid;
            })(dataWords, this.options.data);

            return groupingAreValid && dataAreValid && !lastIsGrouping;

            function separateGroups(groups, isOdd) {
                return _.filter(groups, function(item, i) {
                    return isOdd ? !i % 2 : i % 2;
                });
            }

            function getEscapedOps(ops) {
                var result = [];

                if (ops && ops.length) {
                    _.each(ops, function(item) {
                        result.push('\\' + item.split('').join('\\'));
                    });
                }

                return result;
            }
        },

        autocomplete: function() {
        }
    });

    return RuleEditorComponent;
});
