<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;

use Doctrine\DBAL\Connection;

use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
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

        $expected = (new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_ALIAS)
        )->__toString();
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

        $expected = (new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS)
        )->__toString();
        $listener->onResultBefore($event);

        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNotFilteredByDefault()
    {
        $listener = new CategoryVisibilityGridListener();
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::CONFIG)
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
     * @return \PHPUnit_Framework_MockObject_MockObject|OrmResultBefore
     */
    protected function getOrmResultBeforeEvent($gridName, ParameterBag $bag)
    {
        $dataGrid = $this->getDatagrid($gridName, $bag);
        $event = new OrmResultBefore($dataGrid, new Query($this->getEntityManger()));

        return $event;
    }

    /**
     * @param string       $gridName
     * @param ParameterBag $bag
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridInterface
     */
    protected function getDatagrid($gridName, ParameterBag $bag)
    {
        $qb = new QueryBuilder($this->getEntityManger());
        $qb->where(sprintf("%s IN(1)", CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS));

        $dataSource = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);


        $dataGrid = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
            ->getMock();

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
    protected function getEntityManger()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $em = EntityManagerMock::create($connection);

        return $em;
    }
}
