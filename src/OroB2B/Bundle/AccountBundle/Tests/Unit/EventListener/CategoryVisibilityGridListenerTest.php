<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\EventListener\CategoryVisibilityGridListener;

class CategoryVisibilityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnResultBefore()
    {
        $listener = new CategoryVisibilityGridListener();
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::PARENT_CATEGORY)
        );

        $expected = (string)(new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_ALIAS)
        );
        $listener->onResultBefore($event);

        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeForGroups()
    {
        $listener = new CategoryVisibilityGridListener();
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountGroupCategoryVisibility::PARENT_CATEGORY)
        );

        $expected = (string)(new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS)
        );
        $listener->onResultBefore($event);

        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNotFilteredByDefault()
    {
        $listener = new CategoryVisibilityGridListener();
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::CATEGORY)
        );
        $listener->onResultBefore($event);
        $this->assertNull($event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNoFilter()
    {
        $listener = new CategoryVisibilityGridListener();
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag()
        );
        $listener->onResultBefore($event);
        $this->assertNull($event->getQuery()->getDQL());
    }

    /**
     * @param string       $gridName
     * @param ParameterBag $bag
     *
     * @return OrmResultBefore
     */
    protected function getOrmResultBeforeEvent($gridName, ParameterBag $bag)
    {
        return new OrmResultBefore(
            $this->getDatagrid($gridName, $bag),
            new Query($this->getEntityManager())
        );
    }

    /**
     * @param string       $gridName
     * @param ParameterBag $bag
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridInterface
     */
    protected function getDatagrid($gridName, ParameterBag $bag)
    {
        $qb = new QueryBuilder($this->getEntityManager());
        $qb->where(sprintf("%s IN(1)", CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS));

        /** @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid
            ->expects($this->any())
            ->method('getName')
            ->willReturn($gridName);
        $dataGrid
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn($bag);
        $dataGrid->expects($this->any())
            ->method('getDataSource')
            ->willReturn($dataSource);

        return $dataGrid;
    }

    /**
     * @param string|null $visibilityFilterValue
     *
     * @return ParameterBag
     */
    protected function getParameterBag($visibilityFilterValue = null)
    {
        $bag = new ParameterBag();

        if (!$visibilityFilterValue) {
            return $bag;
        }

        $bag->set('_filter', [
            'visibility' => [
                'value' => [
                    $visibilityFilterValue
                ]
            ]
        ]);

        return $bag;
    }

    /**
     * @return EntityManagerMock
     */
    protected function getEntityManager()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();

        return EntityManagerMock::create($connection);
    }
}
