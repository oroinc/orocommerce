requirejs.config({
    paths: {
        lodash: '/bundles/orob2bfrontend/default/js/vendors/lodash.min',
        bootstrapDatepicker: '/bundles/orob2bfrontend/default/js/vendors/bootstrap-datepicker.min',
        raty: '/bundles/orob2bfrontend/default/js/vendors/jquery.raty',
        chosen: '/bundles/orob2bfrontend/default/js/vendors/chosen.jquery.min',
        slick: '/bundles/orob2bfrontend/default/js/vendors/slick.min',
        perfectScrollbar: '/bundles/orob2bfrontend/default/js/vendors/perfect-scrollbar.jquery.min',
        fastclick: '/bundles/orob2bfrontend/default/js/vendors/fastclick'
    },
    shim: {
        'main': {
            deps: ['jquery', 'lodash']
        }
    }
});
