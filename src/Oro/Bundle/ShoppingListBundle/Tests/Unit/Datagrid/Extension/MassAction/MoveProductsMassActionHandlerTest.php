<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassAction;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassActionHandler;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MoveProductsMassActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListRepository;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListTotalManager;

    /** @var MoveProductsMassActionHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [ShoppingList::class, $this->shoppingListRepository],
                    [LineItem::class, $this->lineItemRepository]
                ]
            );

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($key) {
                    return sprintf('*%s*', $key);
                }
            );

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $this->handler = new MoveProductsMassActionHandler(
            $registry,
            $this->translator,
            $this->authorizationChecker,
            $requestStack,
            $this->shoppingListManager,
            $this->shoppingListTotalManager
        );
    }

    public function testHandleUnsupportedRequestMethod(): void
    {
        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                new MoveProductsMassAction(),
                $this->createMock(DatagridInterface::class),
                $this->createMock(IterableResultInterface::class)
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('Request method "GET" is not supported', $result->getMessage());
        $this->assertEquals([], $result->getOptions());
    }

    public function testHandleWithoutShoppingList(): void
    {
        $this->request->setMethod(Request::METHOD_POST);

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                new MoveProductsMassAction(),
                $this->createMock(DatagridInterface::class),
                $this->createMock(IterableResultInterface::class)
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            '*oro.shoppinglist.mass_actions.move_line_items.no_edit_permission_message*',
            $result->getMessage()
        );
        $this->assertEquals([], $result->getOptions());
    }

    public function testHandleWithoutPermission(): void
    {
        $this->request->setMethod(Request::METHOD_POST);

        $shoppingList = new ShoppingList();

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($shoppingList);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $shoppingList)
            ->willReturn(false);

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                new MoveProductsMassAction(),
                $this->createMock(DatagridInterface::class),
                $this->createMock(IterableResultInterface::class),
                ['shopping_list_id' => 42]
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            '*oro.shoppinglist.mass_actions.move_line_items.no_edit_permission_message*',
            $result->getMessage()
        );
        $this->assertEquals([], $result->getOptions());
    }
}
