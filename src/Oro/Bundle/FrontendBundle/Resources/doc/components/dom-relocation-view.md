
Dom Relocation View

Dom Relocation View uses when you need to move dom element from one container to another on browser window resize.
For example: move menu list from top bar to hamburger menu dropdown in cases when you cannot do this using css @media queries.

How to Use

To enable moving an element from one container to another on window resize add 'data-dom-relocation' and 'data-dom-relocation-options'
attributes to corresponding element as it is showing below:
```html
    <div class="element-to-move"
         data-dom-relocation
         data-dom-relocation-options="{
            responsive: [
                {
                    breakpoint: 640
                    moveTo: '#container' // jQuery selector
                }
            ]
         }"
    >
    <!-- Other content -->
    </div>
```
