import _ from 'underscore';
import emailConstraint from 'oroform/js/validator/email';

const constraint = _.clone(emailConstraint);

constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Email';

export default constraint;
