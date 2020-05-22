<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;

class MinimalPricesCombiningStrategyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ShardQueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $insertFromSelectQueryExecutor;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $triggerHandler;

    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shardManager;

    /**
     * @var MinimalPricesCombiningStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(ShardQueryExecutorInterface::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->strategy = new MinimalPricesCombiningStrategy(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->triggerHandler,
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
