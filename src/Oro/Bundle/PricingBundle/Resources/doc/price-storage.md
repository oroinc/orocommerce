# Price Storage

The Pricing bundle enables you to separate price handling from the storage. Out of the box, the bundle is distributed
with a Combined Price Lists ORM based storage. Depending on the chosen storage model, you may implement your own logic for storing and fetching pricing. To set up your own price storage, `ProductPriceStorageInterface` must be implemented.

## ProductPriceStorageInterface

`ProductPriceStorageInterface` consists of 2 methods:

 - **getPrices** - returns an array of `ProductPriceInterface[]` by the requested criteria (`ProductPriceScopeCriteriaInterface`), 
  products, product unit codes, and currencies. Use `ProductPriceDTO` as the implementation of `ProductPriceInterface`.
  - **getSupportedCurrencies** - returns a list of currencies supported by the storage. Currencies should be in ISO 4217 format.
 
Simple CSV Storage example:

```php
<?php

namespace Acme\Bundle\PricingBundle\Storage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * CSV based prices storage.
 */
class CSVFilePriceStorage implements ProductPriceStorageInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $productUnitCodes = null,
        array $currencies = null
    ) {
        $productsBySku = [];
        foreach ($products as $product) {
            $productsBySku[$product->getSku()] = $product;
        }

        $pl = fopen(__DIR__ . '/price_lists/prices.csv', 'r');
        $prices = [];
        $headers = fgetcsv($pl, 1000, ',');

        while (($data = fgetcsv($pl, 1000, ',')) !== false) {
            $data = array_combine($headers, array_values($data));
            if (array_key_exists($data['sku'], $productsBySku)) {
                $prices[] = $this->createPriceDTO($data, $productsBySku[$data['sku']]);
            }
        }

        return $prices;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return ['USD'];
    }

    /**
     * @param array $data
     * @param Product $product
     * @return ProductPriceDTO
     */
    private function createPriceDTO(array $data, Product $product): ProductPriceDTO
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->doctrineHelper->getEntityReference(ProductUnit::class, $data['unit']);

        return new ProductPriceDTO(
            $product,
            Price::create($data['price'], $data['currency']),
            $data['quantity'],
            $productUnit
        );
    }
}
```

## ProductPriceScopeCriteriaInterface

`ProductPriceScopeCriteriaInterface` contains all information that may be needed for price fetching:
 - **Customer**
 - **Website**
 - **Context** - an entity in which context prices are requested. For example Order, Shopping List, etc.

## Replacing default storage

To replace default storage implementation decorate `oro_pricing.storage.prices` service. 
[How to Decorate Services](https://symfony.com/doc/current/service_container/service_decoration.html)

Service definition example:
```yaml
acme_pricing.storage.csv_file:
    class: Acme\Bundle\PricingBundle\Storage\CSVFilePriceStorage
    public: false
    decorates: oro_pricing.storage.prices
    arguments:
        - '@oro_entity.doctrine_helper'
```

### Disable Oro Pricing

Oro pricing is controlled by the `oro_pricing` feature. It may be disabled by switching off the appropriate system config option
or by voting `VoterInterface::FEATURE_DISABLED` for the `oro_pricing` feature. 
[For more information, see Feature Toggle Bundle](https://github.com/oroinc/platform/blob/master/src/Oro/Bundle/FeatureToggleBundle/README.md).

Voter example:
```php
<?php

namespace Acme\Bundle\PricingBundle\Feature;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Disable oro_pricing
 */
class PricingVoter implements VoterInterface
{
    const PRICING_FEATURE_NAME = 'oro_pricing';

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::PRICING_FEATURE_NAME) {
            return VoterInterface::FEATURE_DISABLED;
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
```
