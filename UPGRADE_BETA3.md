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

MenuBundle:
--------------
- Entity `OroB2B\Bundle\MenuBundle\Entity\MenuItem` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `titles`.

ProductBundle:
--------------
- Entity `OroB2B\Bundle\ProductBundle\Entity\Product` now uses an entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` for `names`, `desciptions` and `shorDescriptions`.
- Replaced single product image with typed product image collection

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
--------------
- Added `OroB2B/Bundle/OrderBundle/Layout/DataProvider/OrderPaymentMethodProvider` in order to get payment method by `Order` object.
- Added `Payment Method` and `Payment Status` data to order tables and views on frontend and admin side.
- Added `get_payment_status_label` twig function in order to show payment status by order id.

CheckoutBundle:
--------------
- Payment Method table filters removed.

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
