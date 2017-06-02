UPGRADE FROM 1.2 to 1.3
=======================

WebsiteSearchBundle
-------------------
- Class `Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataListener` was replaced with `Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataFixturesListener`
- Service `oro_website_search.event_listener.reindex_demo_data` was replaced with `oro_website_search.migration.demo_data_fixtures_listener.reindex`

ProductBundle
-------------
- Class `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener`
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\ProductBundle\Provider\VariantFieldProvider` added.
    - `onBuildAfterEditGrid(BuildAfter $event)` method was added
- Class `Oro\Bundle\ProductBundle\Form\EventSubscriber\ProductVariantFieldsSubscriber` was removed
- Class `Oro\Bundle\ProductBundle\Form\Extension\EnumValueForProductExtension`
     - changed signature of `__construct` method. New dependency on `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` added.
- Form type `Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType`
    - changed signature of `__construct` method. Two previous arguments was replaced on `Oro\Bundle\ProductBundle\Provider\VariantFieldProvider`.
    - method `onPreSetData(FormEvent $event)` was added
- Form type `Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType`
    - changed signature of `__construct` method. `Oro\Bundle\ProductBundle\Provider\CustomFieldProvider` replaced on `Oro\Bundle\ProductBundle\Provider\VariantFieldProvider`.
- Class `Oro\Bundle\ProductBundle\Provider\CustomFielfProvider`
    - removed `getVariantFields($entityName)`
- New class `Oro\Bundle\ProductBundle\Provider\VariantFieldProvider` was added it introduces logic to fetch variant field for certain family
  calling `getVariantFields(AttributeFamily $attributeFamily)` method
- New class `Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator`

PaymentBundle
-------------
- Previously deprecated interface `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` is removed now.
- Previously deprecated class`Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` is removed, `Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider` should be used instead.

PayPalBundle
--------------
- Class `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway`
    - constants `PRODUCTION_HOST_ADDRESS`, `PILOT_HOST_ADDRESS`, `PRODUCTION_FORM_ACTION`, `PILOT_FORM_ACTION` removed.
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface` added. It is used to get required parameters instead of constants.
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListen`
    - property `$allowedIPs` changed from `private` to `protected`
