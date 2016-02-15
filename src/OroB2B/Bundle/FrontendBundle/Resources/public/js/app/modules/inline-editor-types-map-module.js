define(function(require) {
    'use strict';

    var frontendTypeMap = require('oroform/js/tools/frontend-type-map');

    frontendTypeMap.title = {
        viewer: require('orob2bfrontend/js/app/views/viewer/title-view'),
        viewerWrapper: require('orob2bfrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('orob2bfrontend/js/app/views/editor/title-editor-view')
    };
    frontendTypeMap.multilineText = {
        viewer: require('orob2bfrontend/js/app/views/viewer/text-view'),
        viewerWrapper: require('orob2bfrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('orob2bfrontend/js/app/views/editor/multiline-text-editor-view')
    };
});
