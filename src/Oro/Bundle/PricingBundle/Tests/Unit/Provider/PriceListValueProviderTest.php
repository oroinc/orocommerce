<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListValueProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class PriceListValueProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject  */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject  */
    private $aclHelper;

    /** @var PriceListValueProvider */
    private $priceListValueProvider;

    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->priceListValueProvider = new PriceListValueProvider(
            $this->shardManager,
            $this->doctrine,
            $this->aclHelper
        );
    }

    public function testGetPriceListIdWhenShardingDisabled(): void
    {
        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->aclHelper->expects(self::never())
            ->method('apply');

        $priceListId = $this->priceListValueProvider->getPriceListId();
        self::assertNull($priceListId);
    }

    public function testGetPriceListIdWhenShardingEnabled(): void
    {
        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $defaultPriceListId = 1;

        $repo = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($qb);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($defaultPriceListId);

        $priceListId = $this->priceListValueProvider->getPriceListId();
        self::assertSame($defaultPriceListId, $priceListId);
    }
}
