UPGRADE FROM 1.2 to 1.3

ShippingBundle
-------------
- Create files
    - `shipping-methods-grid.less`
- Updated files
    - `style.less`
    - `translations/messages.en.yml` - added new translations for shipping methods table
    - `views/Form/fields.html.twig` - added shipping datagrid table markup
    - `ShippingMethodsConfigsRule/update.html.twig` - added collapse markup and updated fields attributes
    - `widget/collapse-widget.js` - extended jQueryUI collapse widget, add global trigger
    - `views/shipping-rule-method-view.js` - extended and refactored shipping rule component, add functionality for add/delete new shipping methods
