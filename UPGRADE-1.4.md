UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle
 
PaymentBundle
-------------
- Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each
payment method. Use generic `oro_payment.require_payment_redirect` event instead.

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    - `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
- Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'
- Import/export functionality was added to `PriceAttributeProductPrice` entity and import/export buttons were added on product index page. 
  While importing with `Add and replace` strategy, it is possible to remove existing record by setting empty value for price in the file.
  While importing with `Reset and add` strategy, only prices with attributes that are listed in the file will be removed.
    - Class `Oro\Bundle\PricingBundle\ImportExport\Configuration\PriceAttributeProductPriceImportExportConfigurationProvider` was added to show import/export buttons of PriceAttributeProductPrice entity on product index page
    - Class `Oro\Bundle\PricingBundle\ImportExport\Normalizer\PriceAttributeProductPriceNormalizer` was added to exclude quantity field from export
    - Class `Oro\Bundle\PricingBundle\ImportExport\Normalizer\ProductPriceNormalizer` was added to exclude priceList field from export
    - Class `Oro\Bundle\PricingBundle\ImportExport\DataConverter\PriceAttributeProductPriceDataConverter` was added to provide headers for import/export files
    - Class `Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportResetStrategy` was added to remove attribute prices which attributes are listed in an file while importing with "Reset and add" strategy
    - Class `Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportStrategy` was added to provide `Add and replace` import strategy functionality
    - Class `Oro\Bundle\PricingBundle\ImportExport\TemplateFixture\PriceAttributeProductPriceFixture` was added to provide `Download template` functionality
    - Class `Oro\Bundle\PricingBundle\ImportExport\Writer\PriceAttributeProductPriceWriter` was added to remove existing prices that have empty value in the file or update attribute price with a value from the file

ProductBundle
-------------
- Class `Oro\Bundle\ProductBundle\ImportExport\Configuration\ProductImportExportConfigurationProvider` was added to show import/export buttons of Product entity on product index page

PayPalBundle
------------
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. Dependency on `PaymentMethodProviderInterface` added.
