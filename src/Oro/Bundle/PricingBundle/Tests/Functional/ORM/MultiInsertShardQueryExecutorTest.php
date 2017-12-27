<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ORM;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\MultiInsertShardQueryExecutor;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MultiInsertShardQueryExecutorTest extends WebTestCase
{
    /**
     * @var MultiInsertShardQueryExecutor
     */
    protected $insertSelectExecutor;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class]);
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);
        $this->insertSelectExecutor = $this->getContainer()->get('oro_pricing.orm.multi_insert_shard_query_executor');
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    public function testInsert()
    {
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = $this->em->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $this->em->createQueryBuilder();
        $qb->select([
            'UUID()',
            'IDENTITY(prices.product)',
            'prices.productSku',
            'prices.quantity',
            'IDENTITY(prices.unit)',
            'prices.value',
            (string)$priceListInto->getId(),
            'prices.currency',
        ])
            ->from('OroPricingBundle:ProductPrice', 'prices')
            ->leftJoin(
                'OroPricingBundle:ProductPrice',
                'p_check',
                'WITH',
                $qb->expr()->andX(
                    'prices.product = p_check.product',
                    'prices.quantity = p_check.quantity',
                    'prices.unit = p_check.unit',
                    'prices.currency = p_check.currency',
                    'p_check.priceList = :targetPriceList'
                )
            )
            ->where('prices.priceList = :priceList')
            ->andWhere('p_check.id IS NULL')
            ->setParameter('priceList', $priceListFrom)
            ->setParameter('targetPriceList', $priceListInto);

        $fields = ['id','product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $this->insertSelectExecutor->execute(ProductPrice::class, $fields, $qb);

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }
}
