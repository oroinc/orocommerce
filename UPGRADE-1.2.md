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

OrderBundle
-----------
- Added API for:
    - `Oro\Bundle\OrderBundle\Entity\Order`
    - `Oro\Bundle\OrderBundle\Entity\OrderDiscount`
    - `Oro\Bundle\OrderBundle\Entity\OrderLineItem`
    - `Oro\Bundle\OrderBundle\Entity\OrderAddress`
    - `Oro\Bundle\OrderBundle\Entity\OrderShippingTracking`
