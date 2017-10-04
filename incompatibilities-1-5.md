- [CheckoutBundle](#checkoutbundle)
- [InventoryBundle](#inventorybundle)
- [SaleBundle](#salebundle)
- [ShoppingListBundle](#shoppinglistbundle)

CheckoutBundle
--------------
* The `CheckoutGridHelper`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper.php#L17 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper")</sup> class was removed.
* The `CheckoutRepository::getSourcePerCheckout`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository.php#L53 "Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository::getSourcePerCheckout")</sup> method was removed.
* The `CheckoutGridListener::__construct(UserCurrencyManager $currencyManager, CheckoutRepository $checkoutRepository, TotalProcessorProvider $totalProcessor, EntityNameResolver $entityNameResolver, Cache $cache, CheckoutGridHelper $checkoutGridHelper)`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener.php#L76 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener")</sup> method was changed to `CheckoutGridListener::__construct(UserCurrencyManager $currencyManager, CheckoutRepository $checkoutRepository, TotalProcessorProvider $totalProcessor, EntityNameResolver $entityNameResolver)`<sup>[[?]](https://github.com/laboro/dev/tree/ticket/BB-11758/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener.php#L63 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener")</sup>
* The following properties in class `CheckoutGridListener`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener.php#L41 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener")</sup> were removed:
   - `$cache`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener.php#L41 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener::$cache")</sup>
   - `$checkoutGridHelper`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener.php#L61 "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener::$checkoutGridHelper")</sup>

InventoryBundle
---------------
* The `CreateOrderLineItemValidationListener::$requestStack`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\InventoryBundle\EventListener\CreateOrderLineItemValidationListener.php#L39 "Oro\Bundle\InventoryBundle\EventListener\CreateOrderLineItemValidationListener::$requestStack")</sup> property was removed.

SaleBundle
----------
* The `QuoteCheckoutLineItemDataProvider`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\SaleBundle\Provider\QuoteCheckoutLineItemDataProvider.php#L8 "Oro\Bundle\SaleBundle\Provider\QuoteCheckoutLineItemDataProvider")</sup> class was removed.

ShoppingListBundle
------------------
* The `CheckoutLineItemDataProvider`<sup>[[?]](https://github.com/laboro/dev/tree/master/package\commerce\src\Oro\Bundle\ShoppingListBundle\DataProvider\CheckoutLineItemDataProvider.php#L12 "Oro\Bundle\ShoppingListBundle\DataProvider\CheckoutLineItemDataProvider")</sup> class was removed.

