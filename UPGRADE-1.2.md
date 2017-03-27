UPGRADE FROM 1.1 to 1.2
=======================

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