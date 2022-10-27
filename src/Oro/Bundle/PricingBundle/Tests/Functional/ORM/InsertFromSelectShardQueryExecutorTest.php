<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ORM;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class InsertFromSelectShardQueryExecutorTest extends WebTestCase
{
    private InsertFromSelectShardQueryExecutor $insertSelectExecutor;
    private ShardManager $shardManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class]);

        $this->insertSelectExecutor = $this->getContainer()->get('oro_pricing.orm.insert_from_select_query_executor');
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    /**
     * @covers InsertFromSelectShardQueryExecutor::execute
     * @covers InsertFromSelectShardQueryExecutor::executeNative
     */
    public function testInsert()
    {
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $repository->createQueryBuilder('prices')
            ->select([
                'UUID()',
                'IDENTITY(prices.product)',
                'prices.productSku',
                'prices.quantity',
                'IDENTITY(prices.unit)',
                'prices.value',
                (string)$priceListInto->getId(),
                'prices.currency',
            ])
            ->where('prices.priceList = :priceList')
            ->setParameter('priceList', $priceListFrom);

        $fields = ['id','product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $this->insertSelectExecutor->execute(ProductPrice::class, $fields, $qb);

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }
}
