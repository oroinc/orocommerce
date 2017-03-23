UPGRADE FROM 1.0.0 to 1.1.0
===========================

General
-------
* For upgrade from **1.0.0** use the command:
```bash
php app/console oro:platform:upgrade20 --env=prod --force
```

OrderBundle
-----------
* The method `__construct` has been added to `ExtractLineItemPaymentOptionsListener`. Pass `HtmlTagHelper` as the first argument.


SearchBundle
------------
* IndexationListenerTrait IndexationListenerTrait is deprecated. Logic moved to the listener. Entities' fields to be updated weren't translated into an array of fields to be indexed.