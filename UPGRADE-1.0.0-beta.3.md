Upgrade from beta.2
=========================

FallbackBundle:
---------------
- Entity `OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue` moved to `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue`.
- Entity Trait `OroB2B\Bundle\FallbackBundle\Entity\FallbackTrait` moved to `Oro\Bundle\LocaleBundle\Entity\FallbackTrait`.
- Model `OroB2B\Bundle\FallbackBundle\Model\FallbackType` moved to `Oro\Bundle\LocaleBundle\Model\FallbackType`.
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType` moved to `Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType`.
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType` moved to `Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType`.
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType`.
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType`.
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType`.
- Form DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\MultipleValueTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer`.
- Form DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\FallbackValueTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer`.
- Form DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer`.
- Import DataConverter `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter` moved to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter`.
- Import DataConverter `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter` moved to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter`.
- Import Normalizer `OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter` moved to `Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter`.
- Import Normalizer `OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer` moved to `Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer`.
- Import Strategy `OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` removed. Use `Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` instead.
- Import Strategy `OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy` removed. Use `Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy` instead.
- Test Stub `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub` moved to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub`.
- Test Stub `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueTypeStub` moved to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueTypeStub`.
- Test Stub `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub` moved to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub`.
- Test `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverterTest` moved to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverterTest`.
- Test `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter\PropertyPathTitleDataConverterTest` moved to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter\PropertyPathTitleDataConverterTest`.
- Test `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizerTest` moved to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizerTest`.
- Test `OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Strategy\LocalizedFallbackValueCollectionNormalizerTest` moved to `OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy\LocalizedFallbackValueCollectionNormalizerTest`.

CatalogBundle:
--------------
- Entity `OroB2B\Bundle\CatalogBundle\Entity\Category` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `titles`.
- Added `OroB2B\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions` in order to manage default product options for category. Demo Data Migrations changed also.
- Added `OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision` in order to manage units and precisions for category.
- Added `OroB2B\Bundle\CatalogBundle\Form\CategoryDefaultProductOptionsType` in order to fill default product options with values on category creation and update pages.
- Added `OroB2B\Bundle\CatalogBundle\Form\CategoryUnitPrecisionType` in order to fill unit and precision with values on category creation and update pages.
- Modified `OroB2B\Bundle\CatalogBundle\Entity\Category` added property: `defaultProductOptions`.
- Modified `OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler` added methods in order to update Category with `unitPrecision`.
- Modified `OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType` added option `defaultProductOptions`.
- Added `OroB2B\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider` as tagged service with name name 'orob2b_product.default_product_unit_provider' and priority '10'
  in order to work with `OroB2B\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider` see `ProductBundle`

MenuBundle:
--------------
- Entity `OroB2B\Bundle\MenuBundle\Entity\MenuItem` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `titles`.

ProductBundle:
--------------
- Entity `OroB2B\Bundle\ProductBundle\Entity\Product` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `names`, `desciptions` and `shorDescriptions`.
- Replaced single product image with typed product image collection
- Changed approach of selecting default unitPrecision for product:
  1. `OroB2B\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider` was created as chain provider service which is working with tagged services with name 'orob2b_product.default_product_unit_provider' and priority.
     Service with higher priority number will be processed earlier
  2. `OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider` was renamed as `OroB2B\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider` and tagged with name 'orob2b_product.default_product_unit_provider' and priority '0' (lowest)

WebsiteBundle:
--------------
- Entity `OroB2B\Bundle\WebsiteBundle\Entity\Locale` moved to `Oro\Bundle\LocaleBundle\Entity\Localization`.
- Entity Repository `OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository` moved to `Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository`.
- Entity `OroB2B\Bundle\WebsiteBundle\Entity\Website` now uses an entity `Oro\Bundle\LocaleBundle\Entity\Localization`.
- Entity Event Listener `OroB2B\Bundle\WebsiteBundle\EventListener\ORM\LocaleListener` moved to `Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener`.
- Helper `OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper` moved to `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper`.
- Migration `OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadLocaleData` moved to `Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData`.
- Migration `OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM\LoadLocaleData` moved to `Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationData`.
- Migration `OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\UpdateLocaleData` removed.
- Test DataFixture `OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData` moved to `Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData`.
- Test `OroB2B\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository\LocaleRepository` moved to `Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository\LocalizationRepository`.

OrderBundle:
------------
- Added `OroB2B/Bundle/OrderBundle/Layout/DataProvider/OrderPaymentMethodProvider` in order to get payment method by `Order` object.
- Added `Payment Method` and `Payment Status` data to order tables and views on frontend and admin side.
- Added `get_payment_status_label` twig function in order to show payment status by order id.
- Public method `postLoad` renamed to `createPrice` in `OroB2B/Bundle/OrderBundle/Entity/OrderLineItem`.

CheckoutBundle:
---------------
- Payment Method table filters removed.
- Second argument of method `OroB2B\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController::checkoutAction` changed from `$id` to `WorkflowItem $workflowItem` and third argument `$checkoutType = null` was removed.
- Added ninth argument `WorkflowManager $workflowManager` to constructor of `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout`;
- Protected method `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout::getCheckout` was renamed to `getCheckoutWithWorkflowName`.
- Added second argument to protected method `string $workflowName` to method `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout::isNewCheckoutEntity`.
- Removed fields `workflowItem` and `workflowStep` from entity `OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `OroB2B\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 
- Interface `OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface` no longer implements `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface`.
- Added new property `string $workflowName` to `OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent` and added related `setter` and `getter`.
- Added argument `CheckoutInterface $checkout` to method `OroB2B\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener::getWorkflowName`.

AlternativeCheckoutBundle:
--------------------------
- Removed fields `workflowItem` and `workflowStep` from entity `OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout` - not using `WorkflowAwareTrait` more.

PaymentBundle:
--------------
- Added short label for Payment Methods in order to show it on frontend tables.
- Added transactions demo data for orders demo data.

ShoppingListBundle:
-------------------
- `ShoppingListTotalManager` - removed fourth constructor argument $configManager

FrontendBundle and FrontendTestFrameworkBundle:
-----------------------------------------------
- Introduced `FrontendTestFrameworkBundle`
- `OroB2B\Bundle\FrontendBundle\DependencyInjection\Test\Client` moved to `Oro\Bundle\FrontendTestFrameworkBundle\Test\Client`
- `OroB2B\Bundle\FrontendBundle\DependencyInjection\CompilerPass\TestClientPass` removed, parameter is passed in `FrontendTestFrameworkBundle/Resources/config/services.yml` 
- `Oro\Component\Testing\Fixtures\LoadAccountUserData` moved to `Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData`
- No need to load fixtures after test environment setup using `doctrine:fixture:load`
