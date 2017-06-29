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
- AuthorizeNetBundle extracted into individual package. See [https://github.com/orocommerce/OroAuthorizeNetBundle](https://github.com/orocommerce/OroAuthorizeNetBundle) for details.

CheckoutBundle
--------------
- Class `Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter`
    - method `getSecurityFacade` was replaced with `getAuthorizationChecker`

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
- Adding Brand functionality to ProductBundle
    - New class `Oro\Bundle\ProductBundle\Controller\Api\Rest\BrandController` was added
    - New class `Oro\Bundle\ProductBundle\Controller\BrandController` was added
    - New entity `Oro\Bundle\ProductBundle\Entity\Brand` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandType` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandSelectType` was added
    - New form type `Oro\Bundle\ProductBundle\Form\Type\BrandStatusType` was added
    - New form handler `Oro\Bundle\ProductBundle\Form\Handler\BrandHandler` was added
    - New provider `Oro\Bundle\ProductBundle\Provider\BrandRoutingInformationProvider` was added
    - New provider `Oro\Bundle\ProductBundle\Provider\BrandStatusProvider` was added
    - New service `oro_product.brand.manager.api` registered
    
PaymentBundle
-------------
- Previously deprecated interface `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` is removed now.
- Previously deprecated class`Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` is removed, `Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider` should be used instead.

ShippingBundle
-------------
 - redesign of Shipping Rule edit/create pages - changed Shipping Method Configurations block templates and functionality
 - `\Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType` - added `methods_icons` variable
 - `oroshipping/js/app/views/shipping-rule-method-view` - changed options, functions, functionality
 - `\Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType` - use `showIcon` option instead of `result_template_twig` and `selection_template_twig`
 - `OroShippingBundle:Form:type/result.html.twig` and `OroShippingBundle:Form:type/selection.html.twig` - removed

PayPalBundle
--------------
- Class `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway`
    - constants `PRODUCTION_HOST_ADDRESS`, `PILOT_HOST_ADDRESS`, `PRODUCTION_FORM_ACTION`, `PILOT_FORM_ACTION` removed.
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface` added. It is used to get required parameters instead of constants.
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListen`
    - property `$allowedIPs` changed from `private` to `protected`

SEOBundle
-------------
- metaTitles for `Product`, `Category`, `Page`, `WebCatalog`, `Brand` were added. 
MetaTitle is displayed as default view page title.

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
        
PricingBundle
--------------
- Service `oro_pricing.listener.product_unit_precision` was changed from `doctrine.event_listener` to `doctrine.orm.entity_listener`
    - setter methods `setProductPriceClass`, `setEventDispatcher`, `setShardManager` were removed. To set properties, constructor used instead.
    - method `postRemove` has additional argument `ProductUnitPrecision $precision`.
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
