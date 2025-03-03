<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendLineItemsGridVisibilityExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class FrontendLineItemsGridVisibilityExtensionTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ResolvedProductVisibilityProvider&MockObject $resolvedProductVisibilityProvider;
    private ObjectManager&MockObject $lineItemManager;
    private ShoppingListRepository&MockObject $shoppingListRepository;
    private LineItemRepository&MockObject $lineItemRepository;
    private ParameterBag $parameters;
    private FrontendLineItemsGridVisibilityExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->lineItemManager = $this->createMock(ObjectManager::class);
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);

        $this->parameters = new ParameterBag();

        $this->extension = new FrontendLineItemsGridVisibilityExtension(
            $this->registry,
            $this->authorizationChecker,
            $this->resolvedProductVisibilityProvider
        );
        $this->extension->setParameters($this->parameters);

        $this->registry
            ->method('getRepository')
            ->willReturnMap([
                [ShoppingList::class, null, $this->shoppingListRepository],
                [LineItem::class, null, $this->lineItemRepository],
            ]);
    }

    private function createShoppingList(): array
    {
        $product1 = new Product();
        ReflectionUtil::setId($product1, 1001);
        $product1->setSku('PRODUCT_SKU1');
        $product1->setStatus(Product::STATUS_ENABLED);

        $product2 = new Product();
        ReflectionUtil::setId($product2, 2002);
        $product2->setSku('PRODUCT_SKU2');
        $product2->setStatus(Product::STATUS_ENABLED);

        $lineItem1 = new LineItem();
        $lineItem1->setProduct($product1);
        $lineItem2 = new LineItem();
        $lineItem2->setProduct($product2);
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        $this->lineItemRepository->expects(self::once())
            ->method('getItemsWithProductByShoppingList')
            ->with($shoppingList)
            ->willReturn([$lineItem1, $lineItem2]);

        return [$shoppingList, $product1, $product2];
    }

    private function expectFindShoppingList(int $shoppingListId, ?ShoppingList $shoppingList): void
    {
        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with($shoppingListId)
            ->willReturn($shoppingList);
    }

    private static function getDefaultDatagridConfigs(): array
    {
        return [
            'options' => [
                'toolbarOptions' => [
                    'pageSize' => [
                        'items' => [10, 25, 50, 100]
                    ]
                ]
            ],
            'mass_actions' => [
                'move' => [
                    'label' => 'move.label'
                ]
            ]
        ];
    }

    public function testIsApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-grid']);

        self::assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'shopping-list-line-items-grid']);

        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testProcessConfigsWithoutShoppingListId(): void
    {
        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method('prefetch');
        $this->registry->expects(self::never())
            ->method('getManagerForClass');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $config = DatagridConfiguration::create(self::getDefaultDatagridConfigs());
        $this->extension->processConfigs($config);
        self::assertEquals(self::getDefaultDatagridConfigs(), $config->toArray());
    }

    public function testProcessConfigsWithoutShoppingList(): void
    {
        $shoppingListId = 42;
        $this->parameters->set('shopping_list_id', $shoppingListId);
        $this->expectFindShoppingList($shoppingListId, null);

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method('prefetch');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $config = DatagridConfiguration::create(self::getDefaultDatagridConfigs());
        $this->extension->processConfigs($config);
        self::assertEquals(self::getDefaultDatagridConfigs(), $config->toArray());
    }

    public function testProcessConfigs(): void
    {
        $shoppingListId = 42;
        $this->parameters->set('shopping_list_id', $shoppingListId);
        [$shoppingList, $product1, $product2] = $this->createShoppingList();
        $this->expectFindShoppingList($shoppingListId, $shoppingList);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product1->getId(), $product2->getId()]);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($this->lineItemManager);
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturn(true);

        $config = DatagridConfiguration::create(self::getDefaultDatagridConfigs());

        $this->extension->processConfigs($config);

        self::assertEquals(self::getDefaultDatagridConfigs(), $config->toArray());
    }

    public function testProcessConfigsWithInvisibleItems(): void
    {
        $shoppingListId = 42;
        $this->parameters->set('shopping_list_id', $shoppingListId);
        [$shoppingList, $product1, $product2] = $this->createShoppingList();

        $this->expectFindShoppingList($shoppingListId, $shoppingList);
        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product1->getId(), $product2->getId()]);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($this->lineItemManager);
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->lineItemManager->expects(self::once())
            ->method('remove')
            ->with($shoppingList->getLineItems()->get(1));

        $config = DatagridConfiguration::create(self::getDefaultDatagridConfigs());
        $this->extension->processConfigs($config);

        $expectedConfig = self::getDefaultDatagridConfigs();
        $expectedConfig['options']['hiddenLineItems'] = ['PRODUCT_SKU2'];
        self::assertEquals($expectedConfig, $config->toArray());
    }

    public function testVisitResult(): void
    {
        $config = DatagridConfiguration::create(self::getDefaultDatagridConfigs());
        $config->offsetSetByPath(
            FrontendLineItemsGridVisibilityExtension::HIDDEN_LINE_ITEMS_OPTION,
            ['PRODUCT_SKU2']
        );
        $data = ResultsObject::create([]);

        $this->extension->visitResult($config, $data);

        self::assertSame(
            $config->offsetGetByPath(FrontendLineItemsGridVisibilityExtension::HIDDEN_LINE_ITEMS_OPTION),
            $data->offsetGetByPath(FrontendLineItemsGridVisibilityExtension::HIDDEN_LINE_ITEMS_OPTION)
        );
    }
}
