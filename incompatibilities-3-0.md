- [CatalogBundle](#catalogbundle)
- [CheckoutBundle](#checkoutbundle)
- [PricingBundle](#pricingbundle)
- [ProductBundle](#productbundle)
- [PromotionBundle](#promotionbundle)
- [RFPBundle](#rfpbundle)
- [RedirectBundle](#redirectbundle)
- [ShoppingListBundle](#shoppinglistbundle)
- [WebsiteSearchBundle](#websitesearchbundle)

CatalogBundle
-------------
* The following methods in class `ProductStrategyEventListener`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/CatalogBundle/EventListener/ProductStrategyEventListener.php#L60 "Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener")</sup> were removed:
   - `preFlush`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/CatalogBundle/EventListener/ProductStrategyEventListener.php#L60 "Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener::preFlush")</sup>
   - `onClear`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/CatalogBundle/EventListener/ProductStrategyEventListener.php#L91 "Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener::onClear")</sup>

CheckoutBundle
--------------
* The `TransitionFormProvider::setTransitionProvider(TransitionProvider $transitionProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/CheckoutBundle/Layout/DataProvider/TransitionFormProvider.php#L22 "Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider")</sup> method was changed to `TransitionFormProvider::setTransitionProvider(TransitionProviderInterface $transitionProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/CheckoutBundle/Layout/DataProvider/TransitionFormProvider.php#L22 "Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider")</sup>
* The `CheckoutPaymentContextFactory::__construct(CheckoutLineItemsManager $checkoutLineItemsManager, TotalProcessorProvider $totalProcessor, OrderPaymentLineItemConverterInterface $paymentLineItemConverter, PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory = null)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/CheckoutBundle/Factory/CheckoutPaymentContextFactory.php#L41 "Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory")</sup> method was changed to `CheckoutPaymentContextFactory::__construct(CheckoutLineItemsManager $checkoutLineItemsManager, TotalProcessorProvider $totalProcessor, OrderPaymentLineItemConverterInterface $paymentLineItemConverter, ShippingOriginProvider $shippingOriginProvider, PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory = null)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/CheckoutBundle/Factory/CheckoutPaymentContextFactory.php#L52 "Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory")</sup>

PricingBundle
-------------
* The following methods in class `UserCurrencyManager`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Manager/UserCurrencyManager.php#L91 "Oro\Bundle\PricingBundle\Manager\UserCurrencyManager")</sup> were removed:
   - `getLoggedUserCurrentWebsiteCurrency`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Manager/UserCurrencyManager.php#L91 "Oro\Bundle\PricingBundle\Manager\UserCurrencyManager::getLoggedUserCurrentWebsiteCurrency")</sup>
   - `getLoggedUserCurrencyForWebsite`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Manager/UserCurrencyManager.php#L101 "Oro\Bundle\PricingBundle\Manager\UserCurrencyManager::getLoggedUserCurrencyForWebsite")</sup>
* The `PriceListRecalculateCommand::getDependentPriceLists`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Command/PriceListRecalculateCommand.php#L340 "Oro\Bundle\PricingBundle\Command\PriceListRecalculateCommand::getDependentPriceLists")</sup> method was removed.
* The `UserCurrencyManager::getUserCurrency(Website $website = null)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Manager/UserCurrencyManager.php#L71 "Oro\Bundle\PricingBundle\Manager\UserCurrencyManager")</sup> method was changed to `UserCurrencyManager::getUserCurrency(Website $website = null, $fallbackToDefault = true)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/PricingBundle/Manager/UserCurrencyManager.php#L72 "Oro\Bundle\PricingBundle\Manager\UserCurrencyManager")</sup>
* The `CombinedProductPriceRepository::findByPriceListIdAndProductIds(ShardManager $shardManager, $priceListId, array $productIds, $getTierPrices = true, $currency = null, $productUnitCode = null, array $orderBy = [ ... ])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PricingBundle/Entity/Repository/CombinedProductPriceRepository.php#L163 "Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")</sup> method was changed to `CombinedProductPriceRepository::findByPriceListIdAndProductIds(ShardManager $shardManager, $priceListId, array $productIds, $getTierPrices = true, $currency = null, $productUnitCode = null, array $orderBy = [ ... ])`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/PricingBundle/Entity/Repository/CombinedProductPriceRepository.php#L196 "Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")</sup>

ProductBundle
-------------
* The following classes were removed:
   - `ProcessImagePaths`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Api/Processor/Shared/ProcessImagePaths.php#L16 "Oro\Bundle\ProductBundle\Api\Processor\Shared\ProcessImagePaths")</sup>
   - `RelatedItemAclCheck`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Api/Processor/Shared/RelatedItemAclCheck.php#L10 "Oro\Bundle\ProductBundle\Api\Processor\Shared\RelatedItemAclCheck")</sup>
   - `DeleteRelatedItemAclCheck`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Api/Processor/Delete/DeleteRelatedItemAclCheck.php#L10 "Oro\Bundle\ProductBundle\Api\Processor\Delete\DeleteRelatedItemAclCheck")</sup>
* The following methods in class `FrontendLineItemType`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Form/Type/FrontendLineItemType.php#L31 "Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType")</sup> were removed:
   - `__construct`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Form/Type/FrontendLineItemType.php#L31 "Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType::__construct")</sup>
   - `checkUnitSelectionVisibility`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Form/Type/FrontendLineItemType.php#L72 "Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType::checkUnitSelectionVisibility")</sup>
* The `AbstractAjaxProductUnitController::getProductUnits(Product $product, $isShort = false)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ProductBundle/Controller/AbstractAjaxProductUnitController.php#L31 "Oro\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController")</sup> method was changed to `AbstractAjaxProductUnitController::getProductUnits(Product $product)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/ProductBundle/Controller/AbstractAjaxProductUnitController.php#L34 "Oro\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController")</sup>

PromotionBundle
---------------
* The `PromotionExecutor::__construct(DiscountContextConverterInterface $discountContextConverter, StrategyProvider $discountStrategyProvider, PromotionDiscountsProviderInterface $promotionDiscountsProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/PromotionBundle/Executor/PromotionExecutor.php#L35 "Oro\Bundle\PromotionBundle\Executor\PromotionExecutor")</sup> method was changed to `PromotionExecutor::__construct(DiscountContextConverterInterface $discountContextConverter, StrategyProvider $discountStrategyProvider, PromotionDiscountsProviderInterface $promotionDiscountsProvider, Cache $cache)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/PromotionBundle/Executor/PromotionExecutor.php#L42 "Oro\Bundle\PromotionBundle\Executor\PromotionExecutor")</sup>

RFPBundle
---------
* The `SortIncludedData`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/RFPBundle/Api/Processor/SortIncludedData.php#L14 "Oro\Bundle\RFPBundle\Api\Processor\SortIncludedData")</sup> class was removed.

RedirectBundle
--------------
* The `FirewallFactory`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/RedirectBundle/Security/FirewallFactory.php#L9 "Oro\Bundle\RedirectBundle\Security\FirewallFactory")</sup> class was removed.
* The `Firewall::__construct(FirewallMapInterface $map, EventDispatcherInterface $dispatcher, FirewallFactory $firewallFactory, MatchedUrlDecisionMaker $matchedUrlDecisionMaker, RequestContext $context = null)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/RedirectBundle/Security/Firewall.php#L47 "Oro\Bundle\RedirectBundle\Security\Firewall")</sup> method was changed to `Firewall::__construct(FirewallMapInterface $map, EventDispatcherInterface $dispatcher, MatchedUrlDecisionMaker $matchedUrlDecisionMaker, RequestContext $context = null)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/RedirectBundle/Security/Firewall.php#L47 "Oro\Bundle\RedirectBundle\Security\Firewall")</sup>

ShoppingListBundle
------------------
* The `ShoppingListLimitManager::__construct(ConfigManager $configManager, TokenAccessor $tokenAccessor, DoctrineHelper $doctrineHelper)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ShoppingListBundle/Manager/ShoppingListLimitManager.php#L33 "Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager")</sup> method was changed to `ShoppingListLimitManager::__construct(ConfigManager $configManager, TokenAccessor $tokenAccessor, DoctrineHelper $doctrineHelper, WebsiteManager $websiteManager)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/ShoppingListBundle/Manager/ShoppingListLimitManager.php#L38 "Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager")</sup>
* The `ShoppingListRepository::countUserShoppingLists($customerId, $organizationId)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/ShoppingListBundle/Entity/Repository/ShoppingListRepository.php#L126 "Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository")</sup> method was changed to `ShoppingListRepository::countUserShoppingLists($customerId, $organizationId, $website)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0/src/Oro/Bundle/ShoppingListBundle/Entity/Repository/ShoppingListRepository.php#L128 "Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository")</sup>

WebsiteSearchBundle
-------------------
* The `IndexerInputValidator::validateReindexRequest`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/3.0.0-rc/src/Oro/Bundle/WebsiteSearchBundle/Engine/IndexerInputValidator.php#L41 "Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator::validateReindexRequest")</sup> method was removed.

