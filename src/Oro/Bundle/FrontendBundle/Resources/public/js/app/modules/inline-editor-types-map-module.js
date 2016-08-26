define(function(require) {
    'use strict';

    var frontendTypeMap = require('oroform/js/tools/frontend-type-map');

    frontendTypeMap.title = {
        viewer: require('orofrontend/js/app/views/viewer/title-view'),
        viewerWrapper: require('orofrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('orofrontend/js/app/views/editor/title-editor-view')
    };
    frontendTypeMap.text = {
        viewer: require('orofrontend/js/app/views/viewer/text-view'),
        viewerWrapper: require('orofrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('oroform/js/app/views/editor/text-editor-view')
    };
    frontendTypeMap.number = {
        viewer: require('orofrontend/js/app/views/viewer/text-view'),
        viewerWrapper: require('orofrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('oroform/js/app/views/editor/number-editor-view')
    };
    frontendTypeMap.select = {
        viewer: require('orofrontend/js/app/views/viewer/text-view'),
        viewerWrapper: require('orofrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('oroform/js/app/views/editor/select-editor-view')
    };
    frontendTypeMap.multilineText = {
        viewer: require('orofrontend/js/app/views/viewer/text-view'),
        viewerWrapper: require('orofrontend/js/app/views/inline-editable-wrapper-view'),
        editor: require('orofrontend/js/app/views/editor/multiline-text-editor-view')
    };
});
