define(
    ['jquery', 'underscore', 'routing', 'orob2bcatalog/js/lib/jstree/jstree'],
    function ($, _, Routing) {
        'use strict';

        return function (elementId) {
            var $el = $(elementId);

            if (!$el || !$el.length || !_.isObject($el)) {
                throw new Error('Unable to instantiate tree on this element');
            }

            this.config = {
                'core' : {
                    'data' : {
                        'url' : Routing.generate('orob2b_category_list', { _format: 'json'})
                    }
                }
            };

            this.init = function () {
                $el.jstree(this.config)
            };

            this.init();
        }
    }
);
