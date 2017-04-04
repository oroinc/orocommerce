UPGRADE FROM 1.1 to 1.2
=======================

OrderBundle
-------------
- `CHARGE_AUTHORIZED_PAYMENTS` permission was added for possibility to charge payment transaction
- Capture button for payment authorize transactions was added in Payment History section, Capture button for order was removed
- `oro_order_capture` operation was removed, `oro_order_payment_transaction_capture` should be used instead

PaymentBundle
-------------
- For supporting same approaches for working with payment methods, `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` and its implementation were deprecated. Related deprecation is `Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass`. `Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider` which implements `Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface` was added instead. And `Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass` was added for collecting providers in new composite. 
- Class `Oro\Bundle\PaymentBundle\Action\CaptureAction` was removed, `Oro\Bundle\PaymentBundle\Action\PaymentTransactionCaptureAction` should be used instead

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Controller\AjaxPriceListController`
    - method `getPriceListCurrencyList` was renamed to `getPriceListCurrencyListAction`
- Class `Oro\Bundle\PricingBundle\Controller\AjaxProductPriceController`
   - method `getProductPricesByCustomer` was renamed to `getProductPricesByCustomerAction`
- Class `Oro\Bundle\PricingBundle\Controller\Frontend\AjaxProductPriceController`
   - method `getProductPricesByCustomer` was renamed to `getProductPricesByCustomerAction`

ShoppingBundle
-------------
- Class `Oro\Bundle\ShippingBundle\ControllerAjaxProductShippingOptionsController`
    - method `getAvailableProductUnitFreightClasses` was renamed to `getAvailableProductUnitFreightClassesAction`

UPSBundle
-------------
- Class `Oro\Bundle\UPSBundle\Controller`
    - method `getShippingServicesByCountry` was renamed to `getShippingServicesByCountryAction`
    - method `validateConnection` was renamed to `validateConnectionAction`

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
