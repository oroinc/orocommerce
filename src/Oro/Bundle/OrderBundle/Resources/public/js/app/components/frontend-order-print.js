import _ from 'underscore';

export default {
    init: (deferred, options) => {
        const print = () => {
            window.print();
        };

        options.gridPromise.done(_.debounce(print, 500)); // Wait until the grid loading mask is removed.

        return deferred.resolve();
    }
};
