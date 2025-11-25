<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener\FrontendLineItemsGrid;

// @codingStandardsIgnoreStart

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid\InvalidLineItemsActionsOnResultAfterListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

// @codingStandardsIgnoreEnd

class InvalidLineItemsActionsOnResultAfterListenerTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private InvalidLineItemsActionsOnResultAfterListener $listener;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->listener = new InvalidLineItemsActionsOnResultAfterListener($this->authorizationChecker);
    }

    public function testOnResultAfterWithEmptyRecords(): void
    {
        $datagrid = new Datagrid('test_grid', DatagridConfiguration::create([]), new ParameterBag());
        $event = new OrmResultAfter($datagrid, []);

        $this->listener->onResultAfter($event);

        self::assertEmpty($event->getRecords());
    }

    public function testOnResultAfterWithRegularProduct(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => false
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => true,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }

    public function testOnResultAfterWithRegularProductNoEditPermission(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(false);

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => false
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => false,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }

    public function testOnResultAfterWithConfigurableProduct(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $record = new ResultRecord([
            'isConfigurable' => true,
            'isKit' => false,
            'isMatrixFormAvailable' => true,
            'subData' => [
                ['id' => 1, 'name' => 'variant1'],
                ['id' => 2, 'name' => 'variant2']
            ]
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => true,
            'update_product_kit_line_item' => false,
            'delete' => true,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));

        $subData = $record->getValue('subData');
        $expectedSubConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => true,
        ];

        foreach ($subData as $variantData) {
            self::assertEquals($expectedSubConfig, $variantData['action_configuration']);
        }
    }

    public function testOnResultAfterWithConfigurableProductMatrixFormNotAvailable(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $record = new ResultRecord([
            'isConfigurable' => true,
            'isKit' => false,
            'isMatrixFormAvailable' => false,
            'subData' => []
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => true,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }

    public function testOnResultAfterWithProductKit(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => true,
            'validationMetadata' => ['enableProductKitConfigure' => true],
            'subData' => [
                ['id' => 1, 'name' => 'kit_item1'],
                ['id' => 2, 'name' => 'kit_item2']
            ]
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => true,
            'delete' => true,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));

        $subData = $record->getValue('subData');
        $expectedKitItemConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => false,
            'update' => false,
            'oro_shoppinglist_line_item_save_for_later' => false,
            'oro_shoppinglist_line_item_remove_from_saved_for_later' => false,
            'oro_shoppinglist_invalid_line_item_save_for_later' => false,
        ];

        foreach ($subData as $kitItemData) {
            self::assertEquals($expectedKitItemConfig, $kitItemData['action_configuration']);
        }
    }

    public function testOnResultAfterWithProductKitConfigureDisabled(): void
    {
        $shoppingList = (new ShoppingListStub())->setId(1);

        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 1])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => true,
            'validationMetadata' => ['enableProductKitConfigure' => false],
            'subData' => []
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => true,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }

    public function testOnResultAfterWithNoShoppingListId(): void
    {
        $datagrid = new Datagrid('test_grid', DatagridConfiguration::create([]), new ParameterBag());
        $query = $this->createMock(AbstractQuery::class);

        $query->expects(self::never())
            ->method('getEntityManager');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => false
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => false,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }

    public function testOnResultAfterWithNonExistentShoppingList(): void
    {
        $datagrid = new Datagrid(
            'test_grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['shopping_list_id' => 999])
        );

        $entityManager = $this->createMock(EntityManager::class);
        $query = $this->createMock(AbstractQuery::class);

        $entityManager->expects(self::once())
            ->method('find')
            ->with(ShoppingList::class, 999)
            ->willReturn(null);

        $query->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $record = new ResultRecord([
            'isConfigurable' => false,
            'isKit' => false
        ]);

        $event = new OrmResultAfter($datagrid, [$record], $query);
        $this->listener->onResultAfter($event);

        $expectedConfig = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'update_product_kit_line_item' => false,
            'delete' => false,
        ];

        self::assertEquals($expectedConfig, $record->getValue('action_configuration'));
    }
}
