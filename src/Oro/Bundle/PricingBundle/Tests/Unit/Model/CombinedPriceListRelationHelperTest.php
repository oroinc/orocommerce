<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedPriceListRelationHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var CombinedPriceListRelationHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new CombinedPriceListRelationHelper(
            $this->doctrineHelper,
            $this->configManager
        );
    }

    public function testIsFullChainCplForConfig()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.full_combined_price_list')
            ->willReturn(1);
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->helper->isFullChainCpl($cpl));
    }

    public function testIsFullChainCplForRelation()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.full_combined_price_list')
            ->willReturn(1);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['id' => 42]);
        $this->assertQueryBuilderCalls($query);

        $this->assertTrue($this->helper->isFullChainCpl($cpl));
    }

    public function testIsFullChainCplFalse()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.full_combined_price_list')
            ->willReturn(1);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(3))
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $this->assertQueryBuilderCalls($query);

        $this->assertFalse($this->helper->isFullChainCpl($cpl));
    }

    private function assertQueryBuilderCalls(\PHPUnit\Framework\MockObject\MockObject $query): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('createQueryBuilder')
            ->willReturn($qb);
    }
}
