- [CheckoutBundle](#checkoutbundle)
- [FrontendTestFrameworkBundle](#frontendtestframeworkbundle)
- [OrderBundle](#orderbundle)
- [PricingBundle](#pricingbundle)
- [ShoppingListBundle](#shoppinglistbundle)
- [Testing](#testing)
- [VisibilityBundle](#visibilitybundle)
- [WebCatalog](#webcatalog)

CheckoutBundle
--------------
* The `IsWorkflowStartFromShoppingListAllowed::isAllowed`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/CheckoutBundle/Condition/IsWorkflowStartFromShoppingListAllowed.php#L34 "Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed::isAllowed")</sup> method was removed.

FrontendTestFrameworkBundle
---------------------------
* The following methods in class `FrontendWebTestCase`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php#L39 "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase")</sup> were removed:
   - `setCurrentWebsite`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php#L39 "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase::setCurrentWebsite")</sup>
   - `getDefaultWebsiteId`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php#L62 "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase::getDefaultWebsiteId")</sup>

OrderBundle
-----------
* The `TotalCalculateListener::__construct(FormFactory $formFactory, CurrentApplicationProviderInterface $applicationProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/OrderBundle/EventListener/TotalCalculateListener.php#L31 "Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener")</sup> method was changed to `TotalCalculateListener::__construct(FormFactory $formFactory, CurrentApplicationProviderInterface $applicationProvider, FormRegistryInterface $formRegistry)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/OrderBundle/EventListener/TotalCalculateListener.php#L34 "Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener")</sup>

PricingBundle
-------------
* The `PriceListTreeHandler::__construct(ManagerRegistry $registry, WebsiteManager $websiteManager, ConfigManager $configManager)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTreeHandler.php#L47 "Oro\Bundle\PricingBundle\Model\PriceListTreeHandler")</sup> method was changed to `PriceListTreeHandler::__construct(ManagerRegistry $registry, WebsiteManager $websiteManager, ConfigManager $configManager, TokenAccessorInterface $tokenAccessor)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Model/PriceListTreeHandler.php#L62 "Oro\Bundle\PricingBundle\Model\PriceListTreeHandler")</sup>
* The `PriceListTriggerFactory::create(PriceList $priceList, array $productIds = [])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L34 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory")</sup> method was changed to `PriceListTriggerFactory::create(array $products)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L18 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory")</sup>
* The `PriceListTrigger::__construct(PriceList $priceList, array $products = [])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/DTO/PriceListTrigger.php#L24 "Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger")</sup> method was changed to `PriceListTrigger::__construct(array $products = [])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Model/DTO/PriceListTrigger.php#L17 "Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger")</sup>
* The `CombinedProductPriceRepository::findByPriceListIdAndProductIds(ShardManager $shardManager, $priceListId, array $productIds, $getTierPrices = true, $currency = null, $productUnitCode = null, array $orderBy = [ ... ])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Entity/Repository/CombinedProductPriceRepository.php#L158 "Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")</sup> method was changed to `CombinedProductPriceRepository::findByPriceListIdAndProductIds(ShardManager $shardManager, $priceListId, array $productIds, $getTierPrices = true, $currency = null, $productUnitCode = null, array $orderBy = [ ... ])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Entity/Repository/CombinedProductPriceRepository.php#L163 "Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")</sup>
* The following methods in class `PriceListTriggerFactory`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L24 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory")</sup> were removed:
   - `__construct`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L24 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory::__construct")</sup>
   - `getPriceList`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L86 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory::getPriceList")</sup>
* The `PriceListTrigger::getPriceList`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/DTO/PriceListTrigger.php#L33 "Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger::getPriceList")</sup> method was removed.
* The `PriceListProcessor::getCombinedPriceListRepository`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Async/PriceListProcessor.php#L135 "Oro\Bundle\PricingBundle\Async\PriceListProcessor::getCombinedPriceListRepository")</sup> method was removed.
* The `PriceListTriggerFactory::$registry`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/PriceListTriggerFactory.php#L19 "Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory::$registry")</sup> property was removed.
* The `PriceListTrigger::$priceList`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Model/DTO/PriceListTrigger.php#L13 "Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger::$priceList")</sup> property was removed.
* The `PriceListProcessor::$combinedPriceListRepository`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/PricingBundle/Async/PriceListProcessor.php#L55 "Oro\Bundle\PricingBundle\Async\PriceListProcessor::$combinedPriceListRepository")</sup> property was removed.

ShoppingListBundle
------------------
* The `MatrixGridOrderFormProvider::setTwigRenderer(TwigRenderer $twigRenderer)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/ShoppingListBundle/Layout/DataProvider/MatrixGridOrderFormProvider.php#L39 "Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider")</sup> method was changed to `MatrixGridOrderFormProvider::setTwigRenderer(FormRenderer $twigRenderer)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ShoppingListBundle/Layout/DataProvider/MatrixGridOrderFormProvider.php#L39 "Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider")</sup>
* The `ShoppingListTotalListener::__construct(RegistryInterface $registry)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/ShoppingListBundle/EventListener/ShoppingListTotalListener.php#L29 "Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListTotalListener")</sup> method was changed to `ShoppingListTotalListener::__construct(RegistryInterface $registry, ConfigManager $configManager)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ShoppingListBundle/EventListener/ShoppingListTotalListener.php#L45 "Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListTotalListener")</sup>

Testing
-------
* The `AddressFormExtensionTestCase::getTranslatableEntity`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Component/Testing/Unit/AddressFormExtensionTestCase.php#L66 "Oro\Component\Testing\Unit\AddressFormExtensionTestCase::getTranslatableEntity")</sup> method was removed.

VisibilityBundle
----------------
* The `VisibilityChangeGroupSubtreeCacheBuilder::getEntityManager()`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Bundle/VisibilityBundle/Visibility/Cache/Product/Category/Subtree/VisibilityChangeGroupSubtreeCacheBuilder.php#L231 "Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder")</sup> method was changed to `VisibilityChangeGroupSubtreeCacheBuilder::getEntityManager($className)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/VisibilityBundle/Visibility/Cache/Product/Category/Subtree/VisibilityChangeGroupSubtreeCacheBuilder.php#L229 "Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder")</sup>

WebCatalog
----------
* The `PageVariantType::getName`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-beta/src/Oro/Component/WebCatalog/Form/PageVariantType.php#L14 "Oro\Component\WebCatalog\Form\PageVariantType::getName")</sup> method was removed.

