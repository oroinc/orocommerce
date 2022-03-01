<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\PricingStrategy;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\AbstractPriceCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesForCombination;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractPricesCombiningStrategyTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var AbstractPriceCombiningStrategy */
    protected $pricingStrategy;

    /**
     * {@inheritdoc}
     */
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

        $this->pricingStrategy = $this->getContainer()
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
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);
        /** @var ProductUnit $unit */
        $unit = $this->getReference($unitReference);

        $productPrice = new ProductPrice();
        $productPrice->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price)
            ->setQuantity($qty)
            ->setUnit($unit);

        $this->saveProductPrice($productPrice);

        return $productPrice;
    }

    protected function getCombinedPrices(CombinedPriceList $combinedPriceList): array
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);

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
        $registry = $this->getContainer()->get('doctrine');
        $repository = $registry->getRepository(ProductPrice::class);
        /** @var Product $product */
        $criteria['product'] = $this->getReference($criteria['product']);
        if ($criteria['priceList'] === 'default_price_list') {
            $criteria['priceList'] = $registry->getManager()->getRepository(PriceList::class)->getDefault();
        } else {
            /** @var PriceList $priceList */
            $criteria['priceList'] = $this->getReference($criteria['priceList']);
        }
        /** @var ProductUnit $unit */
        $criteria['unit'] = $this->getReference($criteria['unit']);
        unset($criteria['value']);
        $prices = $repository->findByPriceList(
            $this->getContainer()->get('oro_pricing.shard_manager'),
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
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->persist($productPrice);
        $priceManager->flush();
    }

    protected function removeProductPrice(ProductPrice $price): void
    {
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->remove($price);
        $priceManager->flush();
    }
}
