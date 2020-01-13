# Create Editor components

What is GrapesJS component described in following [documentation](https://grapesjs.com/docs/api/component.html)

The way of creating a component type that proposed in GrapesJS docs can be [viewed here](https://grapesjs.com/docs/modules/Components.html#define-custom-component-type).

To simplify adding new types of component there were created component type builders that include to themselves all needed data of a new component type. They implement under hood the same actions that propose in GrapesJS docs but make component type declaration more structured and convenient.

## Type builder

To create own component type first of all create descendant of BaseTypeBuilder

```javascript
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const SomeNewComponent = BaseTypeBuilder.extend({
    button: {
        label: 'Button label',
        category: 'Basic',
        attributes: {
            'class': 'fa fa-hand-pointer-o'
        }
    },

    modelMixin: {
        defaults: {
            classes: ['component-class', 'some-class'],
            tagName: 'span',
            content: 'Inner content'
        },

        someMethod() {
            // Some custom logic
        }
    },

    viewMixin: {
        onRender() {
            // Some custom logic
        }       
    },

    editorEvents: {
        'component:create': 'onCreate',
        'prevent component:selected': 'onSelect'
    },

    commands: {
        'my-command': () => {
            // Some custom logic
        }
    },

    usedTags: ['div', 'span'],

    constructor: function SomeNewComponent(options) {
        SomeNewComponent.__super__.constructor.call(this, options);
    },

    onInit() {
        this.editor.runCommand('my-command');
    },
    
    onCreate() {
        // Some custom logic
    },

    onSelect() {
        // Some custom logic
    }, 

    isComponent(el) {
        let result = null;

        if (el.tagName === 'sometag') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default SomeNewComponent;
```

###Properties overview

`componentType` (String) - name of new component type

`parentType` (String) - name of component type that will be used as parent. If it's not determined, using `default` type

`editor` (Object) - instance of GrapesJS WYSIWYG

`button` (Object) - Data to register panel button (if it is necessary for the new component type)

    `label` (String) - Button label
    `category` (String) - Place button to category container,
    `attributes` (Object) - Object of attributes like class name or data attribute
     
`modelMixin` (Object) Methods and props that will be used to extend WYSIWYG component model. Prop `defaults` will be merged with default model attributes [list of properties](https://grapesjs.com/docs/api/component.html#component)| `{}`     |

`viewMixin` (Object) Methods and props that will be used to extend WYSIWYG component view

`isComponent` (Function) - Identify component type, take a look [documentation](https://grapesjs.com/docs/modules/Components.html#iscomponent)

`onInit` (Function) - Call after component is initialized 

`commads` (Object) - Key is command name and value is command callback

`editorEvents` (Object) - Key is event name and value is name of the builder method

`template` (HTML) - Set component template, if template doesn't set, component user own type for button content
     
`getButtonTemplateData` (Function) - Return data for button template

##Registration new component type

Finally it's necessary to register created type builder in component manager

```javascript
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import SomeTypeBuilder from 'orocms/js/app/grapesjs/type-builders/some-type-builder';

ComponentManager.registerComponentTypes({
    'some': {
        Constructor: SomeTypeBuilder
    }
});
```
The best way to create some appmodule to make sure that builder would be registered before application starts
