<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\PricingStrategy;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\AbstractPriceCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesForCombination;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractPricesCombiningStrategyTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var AbstractPriceCombiningStrategy */
    protected $pricingStrategy;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadProductPricesForCombination::class,
            LoadCombinedPriceLists::class
        ]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        $this->pricingStrategy = self::getContainer()
            ->get('oro_pricing.pricing_strategy.strategy_register')
            ->get($this->getPricingStrategyName());

        $collector = self::getMessageCollector();
        $collector->clear();
    }

    abstract protected function getPricingStrategyName(): string;

    protected function assertCombinedPriceListContainsPrices(
        CombinedPriceList $combinedPriceList,
        array $expectedPrices
    ): void {
        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);
    }

    protected function addProductPrice(
        string $priceListReference,
        string $productReference,
        float $qty,
        string $unitReference,
        Price $price
    ): ProductPrice {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getReference($productReference));
        $productPrice->setPriceList($this->getReference($priceListReference));
        $productPrice->setPrice($price);
        $productPrice->setQuantity($qty);
        $productPrice->setUnit($this->getReference($unitReference));

        $this->saveProductPrice($productPrice);

        return $productPrice;
    }

    protected function getCombinedPrices(CombinedPriceList $combinedPriceList): array
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);
        /** @var CombinedProductPrice[] $prices */
        $prices = $repository->findBy(
            ['priceList' => $combinedPriceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC', 'currency' => 'ASC']
        );

        return $this->formatPrices($prices);
    }

    protected function formatPrices(array $prices): array
    {
        $actualPrices = [];
        foreach ($prices as $price) {
            $actualPrices[$price->getProduct()->getSku()][] = sprintf(
                '%d %s/%d %s',
                $price->getPrice()->getValue(),
                $price->getPrice()->getCurrency(),
                $price->getQuantity(),
                $price->getProductUnitCode()
            );
        }

        return $actualPrices;
    }

    protected function getPriceByReference(string $reference): ?BaseProductPrice
    {
        $criteria = LoadProductPricesForCombination::$data[$reference];
        /** @var ProductPriceRepository $repository */
        $registry = self::getContainer()->get('doctrine');
        $repository = $registry->getRepository(ProductPrice::class);
        $criteria['product'] = $this->getReference($criteria['product']);
        $criteria['priceList'] = $this->getReference($criteria['priceList']);
        $criteria['unit'] = $this->getReference($criteria['unit']);
        unset($criteria['value']);
        $prices = $repository->findByPriceList(
            self::getContainer()->get('oro_pricing.shard_manager'),
            $criteria['priceList'],
            $criteria
        );

        return $prices[0] ?? null;
    }

    protected function getProductIdsFromExpectedPrices(array $expectedPrices): array
    {
        $products = [];
        $productKeys = array_keys($expectedPrices);
        foreach ($productKeys as $productKey) {
            $product = $this->getReference($productKey);
            $products[] = $product->getId();
        }

        return array_unique($products);
    }

    protected function saveProductPrice(ProductPrice $productPrice): void
    {
        $priceManager = self::getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->persist($productPrice);
        $priceManager->flush();
    }

    protected function removeProductPrice(ProductPrice $price): void
    {
        $priceManager = self::getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->remove($price);
        $priceManager->flush();
    }
}
