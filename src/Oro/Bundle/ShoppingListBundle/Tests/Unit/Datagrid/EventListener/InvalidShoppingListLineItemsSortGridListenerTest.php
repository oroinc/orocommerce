<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\InvalidShoppingListLineItemsSortGridListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InvalidShoppingListLineItemsSortGridListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private InvalidShoppingListLineItemsProvider&MockObject $provider;
    private ObjectRepository&MockObject $repository;
    private DatagridInterface $datagrid;

    private InvalidShoppingListLineItemsSortGridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->provider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->datagrid = new Datagrid('test', DatagridConfiguration::create([]), new ParameterBag());

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $this->listener = new InvalidShoppingListLineItemsSortGridListener($this->doctrine, $this->provider);
    }

    public function testOnBuildAfterNoDefaultSort(): void
    {
        $this->datagrid->getParameters()->set('_sort_by', ['sku' => []]);
        $event = new BuildAfter($this->datagrid);

        $this->listener->onBuildAfter($event);

        self::assertNull($event->getDatagrid()->getParameters()->get('invalid_items_ids'));
    }

    public function testOnBuildAfterNoShoppingListId(): void
    {
        $event = new BuildAfter($this->datagrid);

        $this->listener->onBuildAfter($event);

        self::assertNull($event->getDatagrid()->getParameters()->get('invalid_items_ids'));
    }

    public function testOnBuildAfterNoShoppingList(): void
    {
        $this->datagrid->getParameters()->set('shopping_list_id', 1);
        $event = new BuildAfter($this->datagrid);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->listener->onBuildAfter($event);

        self::assertNull($event->getDatagrid()->getParameters()->get('invalid_items_ids'));
    }

    public function testOnBuildAfter(): void
    {
        $shoppingList = new ShoppingList();
        $this->datagrid->getParameters()->set('shopping_list_id', 1);
        $event = new BuildAfter($this->datagrid);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($shoppingList);

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIdsBySeverity')
            ->with(new ArrayCollection([]))
            ->willReturn([2, 1]);

        $this->listener->onBuildAfter($event);

        self::assertSame(
            [2, 1],
            $event->getDatagrid()->getParameters()->get('invalid_items_ids')
        );
    }

    public function testOnResultBeforeQueryNoDefaultSort(): void
    {
        $this->datagrid->getParameters()->set('_sort_by', ['sku' => []]);
        $qb = $this->createMock(QueryBuilder::class);

        $event = new OrmResultBeforeQuery($this->datagrid, $qb);

        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryNoInvalidItemsIds(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $event = new OrmResultBeforeQuery($this->datagrid, $qb);

        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryOnlyWithErrors(): void
    {
        $this->datagrid->getParameters()->set(
            'invalid_items_ids',
            [InvalidShoppingListLineItemsProvider::ERRORS => [2, 1]]
        );
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['invalid_error_line_items', [2, 1]], ['invalid_warning_line_items', []])
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->with('CASE WHEN MIN(lineItem.id) in (:invalid_error_line_items) THEN 1 ' .
                'WHEN MIN(lineItem.id) in (:invalid_warning_line_items) THEN 2 ELSE 3 END');

        $event = new OrmResultBeforeQuery($this->datagrid, $qb);

        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQuery(): void
    {
        $this->datagrid->getParameters()->set(
            'invalid_items_ids',
            [
                InvalidShoppingListLineItemsProvider::ERRORS => [2],
                InvalidShoppingListLineItemsProvider::WARNINGS => [3]
            ]
        );
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['invalid_error_line_items', [2]], ['invalid_warning_line_items', [3]])
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->with('CASE WHEN MIN(lineItem.id) in (:invalid_error_line_items) THEN 1 ' .
                'WHEN MIN(lineItem.id) in (:invalid_warning_line_items) THEN 2 ELSE 3 END');

        $event = new OrmResultBeforeQuery($this->datagrid, $qb);

        $this->listener->onResultBeforeQuery($event);
    }
}
