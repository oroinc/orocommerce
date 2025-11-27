import _ from 'underscore';
import regexConstraint from 'oroform/js/validator/regex';

const constraint = _.clone(regexConstraint);

constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Alphanumeric';

export default constraint;
