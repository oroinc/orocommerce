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

OroCMSBundle
------------
- Content Blocks functionality was added. Please, see [documentation](./src/Oro/Bundle/CMSBundle/README.md) for more information.
