<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\PromotionBundle\EventListener\SelectCouponGridListener;
use Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier;

class SelectCouponGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CouponApplicabilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject
     */
    private $modifier;

    /**
     * @var SelectCouponGridListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->modifier = $this->createMock(CouponApplicabilityQueryBuilderModifier::class);
        $this->listener = new SelectCouponGridListener($this->modifier);
    }

    public function testOnBuildAfterWhenWrongDatasource()
    {
        $datasource = $this->createMock(DatasourceInterface::class);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);

        $this->modifier->expects($this->never())
            ->method('modify');
    }

    public function testOnBuildAfter()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $this->modifier->expects($this->once())
            ->method('modify')
            ->with($queryBuilder);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
