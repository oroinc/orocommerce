# WYSIWYG editor (grapesjs-editor-view.js)

### Device Manager

Default allow breakpoints list:

    allowBreakpoints: [
    	'desktop',
    	'tablet',
    	'tablet-small',
    	'mobile-big',
    	'mobile-landscape',
    	'mobile'
    ],

#### Global module config
Can use twig configuration for JS modules
Set new allow breakpoints list

    'orocms/js/app/grapesjs/grapesjs-editor-view': {
    	allowBreakpoints: ['mobile', 'tablet']
    }

Disable device module control

    'orocms/js/app/grapesjs/grapesjs-editor-view': {
    	disableDeviceManager: true
    }

Also you can use put props directly component

    'page-component' => [
    	'module' => 'oroui/js/app/components/view-component',
    	'options' => [
    		'view' => 'orocms/js/app/grapesjs/grapesjs-editor-view',
    		'disableDeviceManager': true
    	]
    ]
