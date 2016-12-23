#Dom Relocation View

Dom Relocation View uses when you need to move dom element from one container to another on browser window resize.
For example: move menu list from top bar to hamburger menu dropdown in cases when you cannot do this using css @media queries.

##How to Use

To enable moving an element from one container to another on window resize add 'data-dom-relocation' and 'data-dom-relocation-options'
attributes to corresponding element as it is showing below:
```html
    <div class="element-to-move"
         data-dom-relocation
         data-dom-relocation-options="{
            responsive: [
                {
                    screenType: 'tablet'
                    moveTo: '#container' // jQuery selector
                }
            ]
         }"
    >
    <!-- Other content -->
    </div>
```

##Options

###responsive
**Type:** Array

Set multiple moveTo targets for different types of screens.
Like this:
```javascript
responsive: [
    {
        screenType: 'tablet'
        moveTo: '[data-target-example-1]' // jQuery selector
    },
    {
        screenType: 'mobile'
        moveTo: '[data-target-example-2]' // jQuery selector
    }
]
```
It's working with same logic like css @media, so last item of array have higher priority.

###screenType
**Type:** String

**Default:** 'any'

Option describes when should relocate DOM element. All available screen type defined by [Viewport Manager](../../../../../../../../platform/src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/viewport-manager.md).

###moveTo
**Type:** String

Set target selector where should move element.
