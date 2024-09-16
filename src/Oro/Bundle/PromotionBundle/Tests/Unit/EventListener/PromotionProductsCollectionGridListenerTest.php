<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\EventListener\PromotionProductsCollectionGridListener;

class PromotionProductsCollectionGridListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnBuildAfter(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $event = new BuildAfter($datagrid);
        $promotion = new Promotion();
        $organization = new Organization();
        $promotion->setOrganization($organization);
        $parameters = new ParameterBag(['params' => ['promotion' => $promotion]]);

        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('IDENTITY(product.organization) = :orgId')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('orgId', $organization)
            ->willReturn($queryBuilder);

        $listener = new PromotionProductsCollectionGridListener();
        $listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithoutPromotionInParameters(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datasource = $this->createMock(OrmDatasource::class);

        $event = new BuildAfter($datagrid);
        $parameters = new ParameterBag(['params' => []]);

        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);
        $datasource->expects(self::never())
            ->method('getQueryBuilder');

        $listener = new PromotionProductsCollectionGridListener();
        $listener->onBuildAfter($event);
    }
}
