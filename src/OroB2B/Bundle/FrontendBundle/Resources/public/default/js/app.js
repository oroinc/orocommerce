requirejs.config({
    paths: {
        lodash: '/bundles/orob2bfrontend/default/js/vendors/lodash.min',
        bootstrapDatepicker: '/bundles/orob2bfrontend/default/js/vendors/bootstrap-datepicker.min',
        raty: '/bundles/orob2bfrontend/default/js/vendors/jquery.raty',
        chosen: '/bundles/orob2bfrontend/default/js/vendors/chosen.jquery.min',
        slick: '/bundles/orob2bfrontend/default/js/vendors/slick.min',
        main: '/bundles/orob2bfrontend/default/js/main.min',
        perfectScrollbar: '/js/vendors/perfect-scrollbar.jquery.min'
    },
    shim: {
        'main': {
            deps: ['jquery', 'lodash']
        }
    }
});
