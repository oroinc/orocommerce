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
