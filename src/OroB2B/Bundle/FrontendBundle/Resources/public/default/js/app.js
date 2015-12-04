requirejs.config({
    baseUrl: '.',
    paths: {
        jquery: '/js/vendors/jquery.min',
        lodash: '/js/vendors/lodash.min',
        bootstrap: '/css/bootstrap/js/bootstrap.min',
        bootstrapDatepicker: '/js/vendors/bootstrap-datepicker.min',
        raty: '/js/vendors/jquery.raty',
        chosen: '/js/vendors/chosen.jquery.min',
        slick: '/js/vendors/slick.min',
        main: '/js/main.min'
    },
    shim: {
        'main': {
            deps: ['jquery', 'lodash']
        }
    }
});
