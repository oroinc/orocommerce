<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceAttributeProductPriceRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceAttributeProductPrices::class]);
    }

    public function testFindByPriceAttributeProductPriceIdsAndProductIds()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var PriceAttributePriceList $priceAttributePriceList1 */
        $priceAttributePriceList1 = $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1);
        /** @var PriceAttributePriceList $priceAttributePriceList2 */
        $priceAttributePriceList2 = $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2);
        $repo = $this->getContainer()->get('doctrine')
            ->getRepository(PriceAttributeProductPrice::class);
        $result = $repo->findByPriceAttributeProductPriceIdsAndProductIds(
            [$priceAttributePriceList1->getId(), $priceAttributePriceList2->getId()],
            [$product1->getId(), $product2->getId()]
        );
        $this->assertCount(11, $result);
        $result = $repo->findByPriceAttributeProductPriceIdsAndProductIds(
            [$priceAttributePriceList2->getId()],
            [$product1->getId(), $product2->getId()]
        );
        $this->assertCount(4, $result);
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product1 */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productUnit = $this->getReference(LoadProductUnits::LITER);

        $repo = $this->getContainer()->get('doctrine')
            ->getRepository(PriceAttributeProductPrice::class);

        $result = $repo->findBy(['product' => $product, 'unit' => $productUnit]);
        $this->assertCount(3, $result);

        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $repo->deleteByProductUnit($shardManager, $product, $productUnit);

        $result = $repo->findBy(['product' => $product, 'unit' => $productUnit]);
        $this->assertCount(0, $result);
    }

    public function testDeletePricesByPriceList()
    {
        $priceList = $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1);

        /** @var PriceAttributeProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')
            ->getRepository(PriceAttributeProductPrice::class);

        self::assertSame(8, $repository->deletePricesByPriceList($priceList));
    }
}
