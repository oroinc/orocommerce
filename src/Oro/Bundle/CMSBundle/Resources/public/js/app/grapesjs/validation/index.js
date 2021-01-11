import BaseClass from 'oroui/js/base-class';
import idCollision from './id-colision';

const HTMLValidator = BaseClass.extend({
    /**
     * Constraints method collections
     */
    constraints: [
        idCollision
    ],

    constructor: function HTMLValidator(options) {
        HTMLValidator.__super__.constructor.call(this, options);
    },

    validate(str) {
        return this.constraints.reduce((errors, constraint) => {
            return constraint(str);
        }, []);
    }
});

export default HTMLValidator;
