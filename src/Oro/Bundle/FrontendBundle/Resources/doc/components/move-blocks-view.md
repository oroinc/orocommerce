
Move Blocks View
=================

The move blocks is used for moves the elements for DOM in the resize window if it is not able to do this using CSS.

How to Usage
------------
If you want add ability to show element on sticky panel - you should add `data-sticky` attribute to this element
```html
    <div id="flash-messages"
         class="notification"
         data-move-block
         ata-move-options="{
            responsive: [
                {
                    breakpoint: 640
                    moveTo: '#container' // jQuery selector
                }
            ]
         }"
    >
    <!-- Same content -->
    </div>
```
