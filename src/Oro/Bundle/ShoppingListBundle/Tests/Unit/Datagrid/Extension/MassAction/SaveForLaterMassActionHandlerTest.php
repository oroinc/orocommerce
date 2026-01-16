<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\SaveForLaterMassAction;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\SaveForLaterMassActionHandler;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveForLaterMassActionHandlerTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private TranslatorInterface&MockObject $translator;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ShoppingListManager&MockObject $shoppingListManager;
    private ShoppingListTotalManager&MockObject $shoppingListTotalManager;
    private DatagridInterface&MockObject $datagrid;
    private IterableResultInterface&MockObject $iterableResult;
    private ObjectRepository&MockObject $repository;

    private SaveForLaterMassActionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->iterableResult = $this->createMock(IterableResultInterface::class);

        $this->datagrid->expects(self::any())
            ->method('getName')
            ->willReturn('frontend-test-grid');

        $this->registry->expects(self::any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $this->iterableResult->expects(self::any())
            ->method('getSource')
            ->willReturn($this->createMock(QueryBuilder::class));

        $this->handler = new SaveForLaterMassActionHandler(
            $this->registry,
            $this->translator,
            $this->authorizationChecker,
            $this->shoppingListManager,
            $this->shoppingListTotalManager
        );
    }

    public function testHandleNoShoppingListId(): void
    {
        $message = 'You do not have permission to edit the target shopping list.';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shoppinglist.mass_actions.save_for_later.no_edit_permission_message')
            ->willReturn($message);

        $response = $this->handler->handle(
            new MassActionHandlerArgs(new SaveForLaterMassAction(), $this->datagrid, $this->iterableResult)
        );

        self::assertEquals(new MassActionResponse(false, $message), $response);
    }

    public function testHandleNoShoppingList(): void
    {
        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $message = 'You do not have permission to edit the target shopping list.';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shoppinglist.mass_actions.save_for_later.no_edit_permission_message')
            ->willReturn($message);

        $response = $this->handler->handle(
            new MassActionHandlerArgs(
                new SaveForLaterMassAction(),
                $this->datagrid,
                $this->iterableResult,
                ['frontend-test-grid' => ['shopping_list_id' => 1]]
            )
        );

        self::assertEquals(new MassActionResponse(false, $message), $response);
    }

    public function testHandleNoEditPermission(): void
    {
        $shoppingList = new ShoppingList();

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($shoppingList);

        $message = 'You do not have permission to edit the target shopping list.';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shoppinglist.mass_actions.save_for_later.no_edit_permission_message')
            ->willReturn($message);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', $shoppingList)
            ->willReturn(false);

        $response = $this->handler->handle(
            new MassActionHandlerArgs(
                new SaveForLaterMassAction(),
                $this->datagrid,
                $this->iterableResult,
                ['frontend-test-grid' => ['shopping_list_id' => 1]]
            )
        );

        self::assertEquals(new MassActionResponse(false, $message), $response);
    }

    public function testHandleNoDataIdentifier(): void
    {
        $shoppingList = new ShoppingList();

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($shoppingList);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', $shoppingList)
            ->willReturn(true);

        self::expectException(LogicException::class);
        self::expectExceptionMessage('Mass action "test-mass-action" must define identifier name.');

        $massAction = new SaveForLaterMassAction();
        $massAction->getOptions()->setName('test-mass-action');

        $this->handler->handle(
            new MassActionHandlerArgs(
                $massAction,
                $this->datagrid,
                $this->iterableResult,
                ['frontend-test-grid' => ['shopping_list_id' => 1]]
            )
        );
    }
}
