Upgrade from beta.2
===================

AccountBundle:
--------------
- Removed `OroB2B\Bundle\AccountBundle\Helper\AccountUserRolePrivilegesHelper` removed
- Removed `OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRolePrivilegesDataProvider` use `OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRoleOptionsDataProvider` instead
- Removed `OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadWebsiteDefaultRoles` removed
- Removed `OroB2B\Bundle\AccountBundle\Owner\FrontendOwnerTreeProvider` protected method `getTreeData` removed
- Removed `oro/select2-autocomplete-account-parent-component` js component removed from required js config


CatalogBundle:
--------------
- Removed `OroB2B\Bundle\CatalogBundle\Layout\Block\Type\CategoryListType`

- Modified `OroB2B\Bundle\CatalogBundle\Entity\Category` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `titles`.
- Modified `OroB2B\Bundle\CatalogBundle\Entity\Category` added property: `defaultProductOptions`.
- Modified `OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler` added methods in order to update Category with `unitPrecision`.
- Modified `OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType` added option `defaultProductOptions`.


CheckoutBundle:
---------------
- Layout `OroB2B\Bundle\CheckoutBundle\Layout\Block\Type\TransitionButtonType`
- Modified `OroB2B\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`, `Oro\Bundle\ConfigBundle\Config\ConfigManager`


FallbackBundle:
---------------
- Removed `OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` use `Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` instead.
- Removed `OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy` use `Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy` instead.

- Moved `OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue` to `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue`.
- Moved `OroB2B\Bundle\FallbackBundle\Entity\FallbackTrait` to `Oro\Bundle\LocaleBundle\Entity\FallbackTrait`.
- Moved `OroB2B\Bundle\FallbackBundle\Model\FallbackType` to `Oro\Bundle\LocaleBundle\Model\FallbackType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType` to `Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType` to `Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType` to `Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType` to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType` to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\MultipleValueTransformer` to `Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\FallbackValueTransformer` to `Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer`.
- Moved `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer` to `Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer`.
- Moved `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter` to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter`.
- Moved `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter` to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter`.
- Moved `OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter` to `Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter`.
- Moved `OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer` to `Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueTypeStub` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueTypeStub`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\OroRichTextTypeStub` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\OroRichTextTypeStub`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\AbstractLocalizedType` to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\AbstractLocalizedType`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverterTest` to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverterTest`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter\PropertyPathTitleDataConverterTest` to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter\PropertyPathTitleDataConverterTest`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizerTest` to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizerTest`.
- Moved `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Strategy\LocalizedFallbackValueCollectionNormalizerTest` to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy\LocalizedFallbackValueCollectionNormalizerTest`.


FrontendBundle and FrontendTestFrameworkBundle:
-----------------------------------------------
- Introduced `FrontendTestFrameworkBundle`
- Removed `OroB2B\Bundle\FrontendBundle\DependencyInjection\Loader\PrivateYamlFileLoader`.
- Removed `OroB2B\Bundle\FrontendBundle\DependencyInjection\CompilerPass\TestClientPass`. Parameter is passed in `FrontendTestFrameworkBundle/Resources/config/services.yml` 
- Moved `OroB2B\Bundle\FrontendBundle\DependencyInjection\Test\Client` to `Oro\Bundle\FrontendTestFrameworkBundle\Test\Client`
- Moved `Oro\Component\Testing\Fixtures\LoadAccountUserData` to `Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData`
- No need to load fixtures after test environment setup using `doctrine:fixture:load`


InvoiceBundle:
--------------
- Modified `OroB2B\Bundle\InvoiceBundle\Form\Type\InvoiceType` public method `setWebsiteClass` was removed


MenuBundle:
-----------
- Renamed `OroB2B\Bundle\MenuBundle\EventListener\LocaleListener` to `OroB2B\Bundle\MenuBundle\EventListener\LocalizationListener`
- Modified `OroB2B\Bundle\MenuBundle\Layout\Block\Type\MenuType` use `Oro\Component\Layout\Block\OptionsResolver\OptionsResolver` instead `Symfony\Component\OptionsResolver\OptionsResolverInterface`
- Modified `OroB2B\Bundle\MenuBundle\Menu\DatabaseBuilder` rename protected method `getLocale` to `getLocalization`
- Modified `OroB2B\Bundle\MenuBundle\Entity\MenuItem` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `titles`.
- Modified `OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider`
    - required `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper`
    - renamed method `rebuildCacheByLocale` to `rebuildCacheByLocalization`
    - renamed method `clearCacheByLocale` to `clearCacheByLocalization`
    - renamed method `setDefaultLocaleIfNotExists` to `setDefaultLocalizationIfNotExists`


OrderBundle:
------------
- Added `OroB2B/Bundle/OrderBundle/Layout/DataProvider/OrderPaymentMethodProvider` in order to get payment method by `Order` object.
- Added `Payment Method` and `Payment Status` data to order tables and views on frontend and admin side.
- Added `get_payment_status_label` twig function in order to show payment status by order id.

- Removed `OroB2B\Bundle\OrderBundle\Layout\Block\Type\AddressType`
- Removed `OroB2B\Bundle\OrderBundle\Layout\Block\Type\CurrencyType`
- Removed `OroB2B\Bundle\OrderBundle\Layout\Block\Type\DateType`
- Removed `OroB2B\Bundle\OrderBundle\Layout\Block\Type\OrderTotalType`

- Modified `OroB2B/Bundle/OrderBundle/Entity/OrderLineItem` public method `postLoad` renamed to `createPrice`.
- Modified `OroB2B\Bundle\OrderBundle\Controller\Frontend\OrderController` actions `create` and `update` are temporary disabled
- Modified `OroB2B\Bundle\OrderBundle\Entity\OrderLineItem` method `postLoad` renamed to `createPrice`
- Modified `OroB2B\Bundle\OrderBundle\Form\Type\OrderType` method `setWebsiteClass` removed
- Modified `OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\OrderBundle\Provider\ShippingCostSubtotalProvider` 
    - required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
    - method `getBaseCurrency` was removed

PaymentBundle:
--------------
- Modified `OroB2B\Bundle\PaymentBundle\Controller\Frontend\CallbackController`
    - action `callbackReturnAction` is no longer accept `accessToken`
    - action `callbackErrorAction` is no longer accept `accessToken`
- Modified `OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction` method `generate` was removed 
- Modified `OroB2B\Bundle\PaymentBundle\Entity\AbstractCallbackEvent` 
    - method `getPaymentTransaction` was removed
    - method `setPaymentTransaction` was removed
- Modified `OroB2B\Bundle\PaymentBundle\Event\CallbackHandler`
    - required `OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider`
    - method `handle` accept only one attribute `OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent`
    - method `getPaymentTransaction` was removed
- Modified `OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowListener`
    - required `OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry`
    - method `onCallback` was removed
- Modified `OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider` required `OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider`
- Modified `OroB2B\Bundle\PaymentBundle\MethodPaymentMethodInterface` method `execute` required string `action`
- Modified `OroB2B\Bundle\PaymentBundle\Method\PayflowGateway`
    - class required `OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction`
    - method `execute` required string `action` like `authorize`, `capture` etc
- Modified `OroB2B\Bundle\PaymentBundle\Method\PaymentTerm`
    - method `execute` required string `action` like `authorize`, `capture` etc
- Modified `OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SecureTokenIdentifier` method `generate` was removed
- Modified `OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider`
    - class required `OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider`
    - method `hasSuccessfulTransactions` was renamed to `getSuccessfulTransactions`


PricingBundle:
--------------
- Removed `OroB2B\Bundle\PricingBundle\EventListener\WebsiteFormViewListener`
- Removed `OroB2B\Bundle\PricingBundle\EventListener\WebsiteListener`
- Removed `OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension`
- Removed `OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider` use `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager` instead

- Modified `OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer` required `Symfony\Component\EventDispatcher\EventDispatcherInterface`
- Modified `OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer` required `Symfony\Component\EventDispatcher\EventDispatcherInterface`
- Modified `OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository` method `isCreated` renamed to `isExisting`
- Modified `OroB2B\Bundle\PricingBundle\EventListener\FormViewListener`
   - method `onAccountView` removed
   - method `onAccountGroupView` removed
   - method `getWebsites` removed
   - method `onEntityEdit` removed
   - method `addPriceListInfo` removed
   - method `getPriceListRepository` removed
- Modified `OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`
- Modified `OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider` required `OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager`


ProductBundle:
--------------
- Entity `OroB2B\Bundle\ProductBundle\Entity\Product` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `names`, `desciptions` and `shorDescriptions`.
- Replaced single product image with typed product image collection
- Changed approach of selecting default unitPrecision for product:
  1. `OroB2B\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider` was created as chain provider service which is working with tagged services with name 'orob2b_product.default_product_unit_provider' and priority.
     Service with higher priority number will be processed earlier
  2. `OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider` was renamed as `OroB2B\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider` and tagged with name 'orob2b_product.default_product_unit_provider' and priority '0' (lowest)
- Modified `OroB2B\Bundle\ProductBundle\Form\Type\ProductType` required `OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface`


ShoppingListBundle:
-------------------
- `ShoppingListTotalManager` - removed fourth constructor argument $configManager
- Removed `OroB2B\Bundle\ShoppingListBundle\EventListener\LineItemListener`
- Removed `OroB2B\Bundle\ShoppingListBundle\Layout\Block\Type\ShoppingListSelectorType`
- Modified `OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList`
    - method `getTotal` removed
    - method `setTotal` removed
    - method `getCurrency` removed
    - method `setCurrency` removed
- Modified `OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler` removed last constructor argument `OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService`
- Modified `OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider` required `OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListTotalManager`
- Modified `OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager` changed constructor arguments


TaxBundle:
----------
- Removed `OroB2B\Bundle\TaxBundle\Layout\Block\Type\TaxType`

- Modified `OroB2B\Bundle\TaxBundle\Form\Extension\OrderLineItemTypeExtension` use `OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider` instead `OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider`
- Modified `OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider` 
    - removed method `getTax`
    - removed method `isEditMode`
    - removed method `setEditMode`


- ValidationBundle:
-------------------
- Removed `OroB2B\Bundle\ValidationBundle\Validator\Constraints\Count`


WebsiteBundle:
--------------
- Removed `OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\UpdateLocaleData`.
- Removed `OroB2B\Bundle\WebsiteBundle\Controller\WebsiteController`

- Moved `OroB2B\Bundle\WebsiteBundle\Entity\Locale` to `Oro\Bundle\LocaleBundle\Entity\Localization`.
- Moved `OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository` to `Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository`.
- Moved `OroB2B\Bundle\WebsiteBundle\EventListener\ORM\LocaleListener` to `Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener`.
- Moved `OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper` to `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper`.
- Moved `OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadLocaleData` to `Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData`.
- Moved `OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM\LoadLocaleData` to `Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationData`.
- Moved `OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData` to `Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData`.
- Moved `OroB2B\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository\LocaleRepository` to `Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository\LocalizationRepository`.

- Modified `OroB2B\Bundle\WebsiteBundle\Entity\Website` now uses an entity `Oro\Bundle\LocaleBundle\Entity\Localization`.

- Modified `OroB2B\Bundle\WebsiteBundle\Entity\Website` rename method of locales

