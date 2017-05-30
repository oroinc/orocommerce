UPGRADE FROM 1.1 to 1.2

InventoryBundle
---------------
- Class `Oro\Bundle\InventoryBundle\EventListener\CreateOrderLineItemValidationListener`
    - changed signature of `__construct` method. Parameter `RequestStack $requestStack` removed.

MoneyOrderBundle
----------------
- `Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder`
    - removed constant `const TYPE = 'money_order'`

OrderBundle
-------------
- `CHARGE_AUTHORIZED_PAYMENTS` permission was added for possibility to charge payment transaction
- Capture button for payment authorize transactions was added in Payment History section, Capture button for order was removed
- `oro_order_capture` operation was removed, `oro_order_payment_transaction_capture` should be used instead

PaymentBundle
-------------
- For supporting same approaches for working with payment methods, `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` and its implementation were deprecated. Related deprecation is `Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass`. `Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider` which implements `Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface` was added instead. And `Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass` was added for collecting providers in new composite. 
- Class `Oro\Bundle\PaymentBundle\Action\CaptureAction` was removed, `Oro\Bundle\PaymentBundle\Action\PaymentTransactionCaptureAction` should be used instead
- Class `Oro\Bundle\PaymentBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface` added.
    - constant `FAILED_SHIPPING_ADDRESS_URL_KEY` was removed
- Class `Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent`
    - method `getTypedEventName` was removed
- Method `\Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::computeStatus` was deprecated, `\Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::getPaymentStatus` should be used instead

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Controller\AjaxPriceListController`
    - method `getPriceListCurrencyList` was renamed to `getPriceListCurrencyListAction`
- Class `Oro\Bundle\PricingBundle\Controller\AjaxProductPriceController`
   - method `getProductPricesByCustomer` was renamed to `getProductPricesByCustomerAction`
- Class `Oro\Bundle\PricingBundle\Controller\Frontend\AjaxProductPriceController`
   - method `getProductPricesByCustomer` was renamed to `getProductPricesByCustomerAction`
- `productUnitSelectionVisible` option of the `Oro\Bundle\PricingBundle\Layout\Block\Type\ProductPricesType` is required now.

ShoppingBundle
-------------
- Class `Oro\Bundle\ShippingBundle\ControllerAjaxProductShippingOptionsController`
    - method `getAvailableProductUnitFreightClasses` was renamed to `getAvailableProductUnitFreightClassesAction`

UPSBundle
-------------
- Class `Oro\Bundle\UPSBundle\Controller`
    - method `getShippingServicesByCountry` was renamed to `getShippingServicesByCountryAction`
    - method `validateConnection` was renamed to `validateConnectionAction`
- Class `Oro\Bundle\UPSBundle\Entity\UPSTransport`
    - property `testMode` is renamed to `upsTestMode`, accessor methods became `isUpsTestMode()`, `setUpsTestMode()`
    - property `apiUser` is renamed to `upsApiUser`, accessor methods became `getUpsApiUser()`, `setUpsApiUser()`
    - property `apiPassword` is renamed to `upsApiPassword`, accessor methods became `getUpsApiPassword()`, `setUpsApiPassword()`
    - property `apiKey` is renamed to `upsApiKey`, accessor methods became `getUpsApiKey()`, `setUpsApiKey()`
    - property `shippingAccountNumber` is renamed to `upsShippingAccountNumber`, accessor methods became `getUpsShippingAccountNumber()`, `setUpsShippingAccountNumber()`
    - property `shippingAccountName` is renamed to `upsShippingAccountName`, accessor methods became `getUpsShippingAccountName()`, `setUpsShippingAccountName()`
    - property `pickupType` is renamed to `upsPickupType`, accessor methods became `getUpsPickupType()`, `setUpsPickupType()`
    - property `unitOfWeight` is renamed to `upsUnitOfWeight`, accessor methods became `getUpsUnitOfWeight()`, `setUpsUnitOfWeight()`
    - property `country` is renamed to `upsCountry`, accessor methods became `getUpsCountry()`, `setUpsCountry()`
    - property `invalidateCacheAt` is renamed to `upsInvalidateCacheAt`, accessor methods became `getUpsInvalidateCacheAt()`, `setUpsInvalidateCacheAt()`

OroCMSBundle
------------
- Content Blocks functionality was added. Please, see [documentation](./src/Oro/Bundle/CMSBundle/README.md) for more information.

LayoutBundle
-------------
 - `isApplicable(ThemeImageTypeDimension $dimension)` method added to `Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface`

AttachmentBundle
-------------
 - `Oro\Bundle\AttachmentBundle\Resizer\ImageResizer` is now responsible for image resizing only. Use `Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager` to store resized images.
 - `ImageResizer::resizeImage(File $image, $filterName)` has 2 parameters only now.

VisibilityBundle
----------------
- Class `\Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider`
    - changed signature of `getProductVisibilityScope` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`
    - changed signature of `getCustomerProductVisibilityScope` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`
    - changed signature of `getCustomerGroupProductVisibilityScope` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`
- Trait `\Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait`
    - changed signature of `getCustomerGroupProductVisibilityResolvedTermByWebsite` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`
    - changed signature of `getCustomerProductVisibilityResolvedTermByWebsite` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`
    - changed signature of `getProductVisibilityResolvedTermByWebsite` method, replaced `\Oro\Bundle\WebsiteBundle\Entity\Website` with `\Oro\Component\Website\WebsiteInterface`

RuleBundle
----------
- Class `Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider`
    - logic moved to the `\Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator`
    - changed signature of `__construct` method, all arguments replaced with - `CanonicalUrlGenerator`
- Following methods were added to `\Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface`:
    - `getBaseSlug`
    - `getSlugByLocalization`

OrderBundle
-----------
- Added API for:
    - `Oro\Bundle\OrderBundle\Entity\Order`
    - `Oro\Bundle\OrderBundle\Entity\OrderDiscount`
    - `Oro\Bundle\OrderBundle\Entity\OrderLineItem`
    - `Oro\Bundle\OrderBundle\Entity\OrderAddress`
    - `Oro\Bundle\OrderBundle\Entity\OrderShippingTracking`

CustomerBundle
--------------
- Class `Oro\Bundle\CustomerBundle\Audit\DiscriminatorMapListener` moved to `Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener`
- `Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest\GridViewController`
    - added api controller based on `Oro\Bundle\DataGridBundle\Controller\Api\Rest\GridViewController ` and override methods:
        postAction(), putAction(), deleteAction(), defaultAction()
- `Oro\Bundle\CustomerBundle\Datagrid\Extension\GridViewsExtension`
    - added class based on `Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension`
- `Oro\Bundle\CustomerBundle\Datagrid\Extension\GridViewsExtensionComposite`
    - added class based on `Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension` and override methods:
        isApplicable(), getPriority(), visitMetadata(), setParameters()
- `Oro\Bundle\CustomerBundle\Entity\GridView`
    - added entity class based on `Oro\Bundle\DataGridBundle\Entity\AbstractGridView` with new field `customer_user_owner_id`
- `Oro\Bundle\CustomerBundle\Entity\GridViewUser`
    - added entity class based on `Oro\Bundle\DataGridBundle\Entity\AbstractGridView` with new field `customer_user_id`
- `Oro\Bundle\CustomerBundle\Entity\Manager\GridViewManagerComposite`
    - added class based on `Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager` and override methods:
        setDefaultGridView(), getSystemViews(), getAllGridViews(), getDefaultView(), getView()
- `Oro\Bundle\CustomerBundle\Entity\Repository\GridViewRepository`
    - added repository class based on `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository` with replaced getOwnerFieldName() and getUserFieldName() to `customerUserOwner` and `customerUser`
- `Oro\Bundle\CustomerBundle\Entity\Repository\GridViewUserRepository`
    - added repository class based on `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository` with replaced getUserFieldName() to `customerUser`

ShoppingListBundle
------------------
- `Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider`
    - changed signature of `__construct` method, third argument `Oro\Bundle\SecurityBundle\SecurityFacade` $securityFacade replaced with `Oro\Bundle\SecurityProBundle\ORM\Walker\AclHelper` $aclHelper
- `Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository`
    - signature of method `getProductItemsWithShoppingListNames` changed
        - $customerUser parameter is removed
        - now method takes two parameters `Oro\Bundle\SecurityProBundle\ORM\Walker\AclHelper` $aclHelper and array of `Oro\Bundle\ProductBundle\Entity\Product` $products
- `Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener`
    - changed signature of `__construct` method, first argument `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage` $tokenStorage replaced with - `Oro\Bundle\SecurityBundle\SecurityFacade` $securityFacade

ShippingBundle
--------------
- `Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod` method deprecated because it completely duplicate `getEnabledRulesByMethod`
- If you have implemented a form that helps configure your custom shipping method (like the UPS integration form that is designed for the system UPS shipping method), you might need your custom shipping method validation. The `Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface` and `oro_shipping.method_validator.basic` service were created to handle this. To add a custom logics, add a decorator for this service. Please refer to `oro_shipping.method_validator.decorator.basic_enabled_shipping_methods_by_rules` example.
- The `Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider` was created,

FlatRateShippingBundle
--------------
- The `Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder` was deprecated, the `Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory` was created instead.

RedirectBundle
--------------
- Class `Oro\Bundle\RedirectBundle\Async\DelayedJobRunnerDecoratingProcessor` moved to `Oro\Component\MessageQueue\Job\DelayedJobRunnerDecoratingProcessor`

CatalogBundle
--------------
- The `Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository::getChildrenWithTitles` was deprecated, the `\Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository::getChildren` was created instead.

ProductBundle
--------------
- The method [`ProductContentVariantReindexEventListener::__construct`](https://github.com/orocommerce/orocommerce/blob/1.1.0/src/Oro/Bundle/ProductBundle/EventListener/ProductContentVariantReindexEventListener.php "Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener") has been updated. Pass `Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener` as a third argument of the method.

PayPalBundle
------------
- Form type `Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType` is deprecated, will be removed in v1.3. Please use `Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType` instead.
- Interface `Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface` is deprecated, will be removed in v1.3. Use `Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface` instead.
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback`
    - changed signature of `__construct` method. New dependency on `Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface` added.
- JS credit card validators were moved to `PaymentBundle`. List of moved components:
    - `oropaypal/js/lib/jquery-credit-card-validator`
    - `oropaypal/js/validator/credit-card-expiration-date`
    - `oropaypal/js/validator/credit-card-expiration-date-not-blank`
    - `oropaypal/js/validator/credit-card-number`
    - `oropaypal/js/validator/credit-card-type`
    - `oropaypal/js/adapter/credit-card-validator-adapter`
