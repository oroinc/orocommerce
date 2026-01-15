<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener\FrontendLineItemsGrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid\ShoppingListLineItemsAccessListener;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListItemsAccessListenerTest extends TestCase
{
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;
    private ManagerRegistry|MockObject $managerRegistry;

    private ShoppingListLineItemsAccessListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->listener = new ShoppingListLineItemsAccessListener(
            $this->authorizationChecker,
            $this->managerRegistry
        );
    }

    public function testOnBuildBeforeWhenAccessGranted()
    {
        $shoppingListId = 1;
        $datagrid = $this->prepareDatagrid($shoppingListId);
        $shoppingList = new ShoppingList();

        $this->assertEntityFind($shoppingListId, $shoppingList);
        $event = new BuildBefore($datagrid, $this->createMock(DatagridConfiguration::class));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $shoppingList)
            ->willReturn(true);

        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWhenAccessIsNotGranted()
    {
        $this->expectException(AccessDeniedException::class);

        $shoppingListId = 1;
        $datagrid = $this->prepareDatagrid($shoppingListId);
        $shoppingList = new ShoppingList();

        $this->assertEntityFind($shoppingListId, $shoppingList);
        $event = new BuildBefore($datagrid, $this->createMock(DatagridConfiguration::class));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $shoppingList)
            ->willReturn(false);

        $this->listener->onBuildBefore($event);
    }

    private function prepareDatagrid(int $id): DatagridInterface
    {
        $parameters = new ParameterBag(['shopping_list_id' => $id]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        return $datagrid;
    }

    private function assertEntityFind(int $id, ShoppingList $shoppingList): void
    {
        $repo = $this->createMock(ShoppingListRepository::class);
        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($shoppingList);
    }
}
