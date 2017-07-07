UPGRADE FROM 1.2 to 1.3
=======================

**IMPORTANT**
-------------

The class `Oro\Bundle\SecurityBundle\SecurityFacade`, services `oro_security.security_facade` and `oro_security.security_facade.link`, and TWIG function `resource_granted` were marked as deprecated.
Use services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` and TWIG function `is_granted` instead.
In controllers use `isGranted` method from `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
The usage of deprecated service `security.context` (interface `Symfony\Component\Security\Core\SecurityContextInterface`) was removed as well.
All existing classes were updated to use new services instead of the `SecurityFacade` and `SecurityContext`:

- service `security.authorization_checker`
    - implements `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
    - the property name in classes that use this service is `authorizationChecker`
- service `security.token_storage`
    - implements `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
    - the property name in classes that use this service is `tokenStorage`
- service `oro_security.token_accessor`
    - implements `Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface`
    - the property name in classes that use this service is `tokenAccessor`
- service `oro_security.class_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker`
    - the property name in classes that use this service is `classAuthorizationChecker`
- service `oro_security.request_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker`
    - the property name in classes that use this service is `requestAuthorizationChecker`

AuthorizeNetBundle
------------------
- AuthorizeNetBundle extracted to individual package. See [https://github.com/orocommerce/OroAuthorizeNetBundle](https://github.com/orocommerce/OroAuthorizeNetBundle) for details.

CatalogBundle
-------------
- Class `Oro\Bundle\CatalogBundle\EventListenerFormViewListener`
    - changed signature of `__construct` method. Dependency on `RequestStack` was removed.

CheckoutBundle
--------------
- Class `Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter`
    - method `getSecurityFacade` was replaced with `getAuthorizationChecker`
- Layout `oro_payment_method_order_review` is deprecated since v1.3, will be removed in v1.6. Use 'oro_payment_method_order_submit' instead.

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
- Class `Oro\Bundle\ProductBundle\Provider\CustomFieldProvider`
    - removed `getVariantFields($entityName)`
- New class `Oro\Bundle\ProductBundle\Provider\VariantFieldProvider` was added it introduces logic to fetch variant field for certain family
  calling `getVariantFields(AttributeFamily $attributeFamily)` method
- New class `Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator`
- Class `Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy`
    - method `setSecurityFacade` was replaced with `setTokenAccessor`
- Class `Oro\Bundle\ProductBundle\Api\Processor\BuildSingleProductQuery` was removed
- Class `Oro\Bundle\ProductBundle\Api\Processor\LoadEntityId` was removed
- Class `Oro\Bundle\ProductBundle\Api\Processor\NormalizeProductId` was removed
- Adding Brand functionality to ProductBundle
    - New class `Oro\Bundle\ProductBundle\Controller\Api\Rest\BrandController` was added
    - New class `Oro\Bundle\ProductBundle\Controller\BrandController` was added
    - New entity `Oro\Bundle\ProductBundle\Entity\Brand` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandType` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandSelectType` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandStatusType` was added
    - New provider `Oro\Bundle\ProductBundle\Provider\BrandRoutingInformationProvider` was added
    - New provider `Oro\Bundle\ProductBundle\Provider\BrandStatusProvider` was added
    - New service `oro_product.brand.manager.api` registered
- Interface `Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface`
    - New method `getHumanReadableValue` was added
- Class `Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler`
    - changed signature of `__construct` method. New dependency on `Symfony\Component\Translation\TranslatorInterface` was added.
- Class `Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler`
    - changed signature of `__construct` method. New dependency on `Psr\Log\LoggerInterface` was added.
- Class `Oro\Bundle\ProductBundle\Provider\ConfigurableProductProvider`
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry` was added.

- Adding skuUppercase to Product entity - the read-only property that consists uppercase version of sku, used to improve performance of searching by SKU 
    - `ProductPriceFormatter` method `formatProductPrice` changed to expect `BaseProductPrice` attribute instead of `ProductPrice`.

PaymentBundle
-------------
- Previously deprecated interface `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` is removed now.
- Previously deprecated class `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` is removed, `Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider` should be used instead.
- Previously deprecated method `Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::computeStatus` is removed. Use `getPaymentStatus` instead.

ShippingBundle
-------------
 - redesign of Shipping Rule edit/create pages - changed Shipping Method Configurations block templates and functionality
 - `\Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType` - added `methods_icons` variable
 - `oroshipping/js/app/views/shipping-rule-method-view` - changed options, functions, functionality
 - `\Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType` - use `showIcon` option instead of `result_template_twig` and `selection_template_twig`
 - `OroShippingBundle:Form:type/result.html.twig` and `OroShippingBundle:Form:type/selection.html.twig` - removed
 - previously deprecated interface `\Oro\Bundle\ShippingBundle\Identifier\IntegrationMethodIdentifierGeneratorInterface` is removed along with its implementations and usages. Use `Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface` instead.
 - previously deprecated `Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod` method is removed now. Use `getEnabledRulesByMethod` method instead.
 - previously deprecated `Oro\Bundle\ShippingBundle\EventListener\AbstractIntegrationRemovalListener` is removed now. Use `Oro\Bundle\ShippingBundle\EventListener\IntegrationRemovalListener` instead.


PayPalBundle
--------------
- Class `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway`
    - constants `PRODUCTION_HOST_ADDRESS`, `PILOT_HOST_ADDRESS`, `PRODUCTION_FORM_ACTION`, `PILOT_FORM_ACTION` removed.
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface` added. It is used to get required parameters instead of constants.
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListen`
    - property `$allowedIPs` changed from `private` to `protected`
- Previously deprecated `Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType` is removed. Use `Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType` instead.
- Previously deprecated interface `Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface` is removed. Use `Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface` instead.

SEOBundle
-------------
- metaTitles for `Product`, `Category`, `Page`, `WebCatalog`, `Brand` were added.
MetaTitle is displayed as default view page title.
- Class `Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener`
    - changed signature of `__construct` method:
        - dependency on `RequestStack` was removed
        - dependency on `DoctrineHelper` was removed
    - method `setBlockPriority` was removed
- Service `oro_seo.event_listener.product_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
- Service `oro_seo.event_listener.category_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
- Service ` oro_seo.event_listener.page_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
- Service `oro_seo.event_listener.content_node_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed


PaymentBundle
-------------
- Subtotal and currency of payment context and its line items are optional now:
    - Interface `Oro\Bundle\PaymentBundle\Context\PaymentContextInterface` was changed:
        - `getSubTotal` method can return either `Price` or `null`
        - `getCurrency` method can return either `string` or `null`
    - Interface `Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface` was changed:
        - `getPrice` method can return either `Price` or `null`
    - Interface `Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface` was changed (the implementations were changed as well):
        - `setSubTotal` method is added
        - `setCurrency` method is added
    - Interface `Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface` was changed (the implementations were changed as well):
        - `$currency` and `$subtotal` are removed from `createPaymentContextBuilder()` method signature
    - Interface `Oro\Bundle\PaymentBundle\Context\LineItem\Builder\PaymentLineItemBuilderInterface` was changed (the implementations were changed as well):
        - `setPrice` method is added
    - Interface `Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface` was changed (the implementations were changed as well):
        - `$price` is removed from `createBuilder()` method signature
- Unused abstract classes `Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig` and `Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentSystemConfig` was removed.
- Unused trait `Oro\Bundle\PaymentBundle\Method\Config\CountryAwarePaymentConfigTrait` was removed.
- Unused interface `Oro\Bundle\PaymentBundle\Method\Config\CountryConfigAwareInterface` was removed.

RFPBundle
---------
- Class `Oro\Bundle\RFPBundle\Controller\Frontend\RequestController`
    - removed method `getSecurityFacade`

ShippingBundle
--------------
- Subtotal and currency of shipping context and its line items are optional now:
    - Interface `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface` was changed:
        - `getSubTotal` method can return either `Price` or `null`
        - `getCurrency` method can return either `string` or `null`
    - Interface `Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface` was changed:
        - `getPrice` method can return either `Price` or `null`
    - Interface `Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface` was changed (the implementations were changed as well):
        - `setSubTotal` method is added
        - `setCurrency` method is added
    - Interface `Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface` was changed (the implementations were changed as well):
        - `$currency` and `$subtotal` are removed from `createShippingContextBuilder()` method signature
    - Interface `Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface` was changed (the implementations were changed as well):
        - `setPrice` method is added
    - Interface `Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface` was changed (the implementations were changed as well):
        - `$price` is removed from `createBuilder()` method signature
- Class `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider` which never used was removed.
- Added interface `Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface` and class `Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider` which implement this interface.
- Class `Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry` was renamed to `Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider`
    - method `getTrackingAwareShippingMethods` moved to class `Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider`
- Service `oro_shipping.shipping_method.registry` was replaced with `oro_shipping.shipping_method_provider`

PricingBundle
--------------
- Service `oro_pricing.listener.product_unit_precision` was changed from `doctrine.event_listener` to `doctrine.orm.entity_listener`
    - setter methods `setProductPriceClass`, `setEventDispatcher`, `setShardManager` were removed. To set properties, constructor used instead.
    - method `postRemove` has additional argument `ProductUnitPrecision $precision`.
- Class `Oro\Bundle\PricingBundle\EventListener\FormViewListener`
    - changed signature of `__construct` method:
        - dependency on `RequestStack` was removed.
        - dependency on `Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider` was added.
- Added API for entities:
    - `Oro\Bundle\PricingBundle\Entity\PriceList`
    - `Oro\Bundle\PricingBundle\Entity\PriceListSchedule`
    - `Oro\Bundle\PricingBundle\Entity\PriceRule`
- Added API processors:
    - `Oro\Bundle\PricingBundle\Api\Processor\HandlePriceListStatusChangeProcessor` to handle price list status changes
    - `Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListLexemesProcessor` to update price rule lexemes while saving price list
    - `Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleDeleteListProcessor` to rebuild combined price list while deleting list of price list schedules
    - `Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleDeleteProcessor` to rebuild combined price list while deleting single price list schedule
    - `Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleSaveProcessor` to rebuild combined price list while saving price list schedule
    - `Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor` to change price list contains schedule field while deleting list of price list schedules
    - `Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteProcessor` to change price list contains schedule field while deleting single price list schedule
    - `Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteListProcessor` to update price rule lexemes while deleting list of price rules
    - `Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteProcessor` to update price rule lexemes while deleting single price rule
    - `Oro\Bundle\PricingBundle\Api\UpdateLexemesPriceRuleProcessor` to update price rule lexemes while saving price rule
- Added `Oro\Bundle\PricingBundle\Api\Form\AddSchedulesToPriceListApiFormSubscriber` for adding currently created schedule to price list

ValidationBundle
--------------
 - Added `Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOf` constraint and `Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOfValidator` validator for validating that one of some fields in a group should be blank

SaleBundle
----------
- Added Voter `Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter`, Checks if given Quote contains internal status, triggered only for Commerce Application.
- Updated entity `Oro\Bundle\SaleBundle\Entity\Quote`
    - Added constant `FRONTEND_INTERNAL_STATUSES` that holds all available internal statuses for Commerce Application
    - Added new property `pricesChanged`, that indicates if prices were changed.
- Added Datagrid Listener `Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendQuoteDatagridListener`, appends frontend datagrid query with proper frontend internal statuses.
- Added Subscriber `Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber`, discards price modifications and free form inputs, if there are no permissions for those operations
- Updated FormType `Oro\Bundle\SaleBundle\Form\Type\QuoteType`
    - changed constructor signature, now it awaits:
        - third argument should be an instance of `Symfony\Component\EventDispatcher\EventSubscriberInterface`
        - fourth argument should be an instance of `Oro\Bundle\SecurityBundle\SecurityFacade`
- Following ACL permissions moved to `Quote` category
    - oro_quote_address_shipping_customer_use_any
    - oro_quote_address_shipping_customer_use_any_backend
    - oro_quote_address_shipping_customer_user_use_default
    - oro_quote_address_shipping_customer_user_use_default_backend
    - oro_quote_address_shipping_customer_user_use_any
    - oro_quote_address_shipping_customer_user_use_any_backend
    - oro_quote_address_shipping_allow_manual
    - oro_quote_address_shipping_allow_manual_backend
    - oro_quote_payment_term_customer_can_override
- Added new permission to `Quote` category
    - oro_quote_prices_override
    - oro_quote_review_and_approve
    - oro_quote_add_free_form_items
- Added new workflow `b2b_quote_backoffice_approvals`

UPSBundle
---------
- Class `Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodIdentifierGenerator` is removed in favor of `Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator`.

FlatRateShippingBundle
----------------------
- Class `Oro\Bundle\FlatRateShippingBundle\Method\Identifier\FlatRateMethodIdentifierGenerator` is removed in favor of `Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator`.
- previously deprecated `Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder` is removed now. Use `Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory` instead.

InventoryBundle
--------------
- Class `Oro\Bundle\InventoryBundle\Api\Processor\BuildSingleInventoryLevelQuery` was removed
- Class `Oro\Bundle\InventoryBundle\Api\Processor\NormalizeInventoryLevelRequestData` was removed
- Previously deprecated class `Oro\Bundle\InventoryBundle\Api\Processor\JsonApi\FixProductUnitPrecisionUnitCodeFilter` was now removed
- Inventory API has changed. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/InventoryBundle/doc/api/inventory-level.md) for more information.

