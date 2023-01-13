import $ from 'jquery';
import config from 'orofrontend/default/js/app/views/filter-settings';

export default $.extend(true, {}, config, {
    appearance: {
        'collapse-mode': {
            criteriaClass: ' btn btn--default btn--size-s btn--full'
        }
    }
});
