import _ from 'underscore';
import urlConstraint from 'oroform/js/validator/url';

const constraint = _.clone(urlConstraint);

constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Url';

export default constraint;
