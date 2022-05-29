<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\PricingStrategy;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\ORM\QueryExecutorProviderInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;

class MinimalPricesCombiningStrategyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var QueryExecutorProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $insertFromSelectQueryExecutor;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var MinimalPricesCombiningStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(QueryExecutorProviderInterface::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->strategy = new MinimalPricesCombiningStrategy(
            $this->doctrine,
            $this->insertFromSelectQueryExecutor,
            $this->shardManager
        );
    }

    public function testGetCombinedPriceListIdentifier()
    {
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $priceListsRelations = [
            new PriceListSequenceMember($priceList2, true),
            new PriceListSequenceMember($priceList1, false),
            new PriceListSequenceMember($priceList1, true),
            new PriceListSequenceMember($priceList2, true),
        ];

        $this->assertEquals(md5('1_2'), $this->strategy->getCombinedPriceListIdentifier($priceListsRelations));
    }
}
