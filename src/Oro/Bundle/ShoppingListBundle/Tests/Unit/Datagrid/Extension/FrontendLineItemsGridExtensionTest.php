<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendLineItemsGridExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendLineItemsGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListRepository;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ParameterBag */
    private $parameters;

    /** @var FrontendLineItemsGridExtension */
    private $extension;

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

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->parameters = new ParameterBag();

        $this->extension = new FrontendLineItemsGridExtension($registry, $this->configManager, $this->tokenAccessor);
        $this->extension->setParameters($this->parameters);
    }

    public function testIsApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-grid']);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'shopping-list-line-items-grid']);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testSetParameters(): void
    {
        $this->extension->setParameters(
            new ParameterBag(
                [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        'g' => [
                            'group' => true,
                        ],
                    ],
                ],
            )
        );

        $this->assertEquals(
            [
                ParameterBag::MINIFIED_PARAMETERS => [
                    'g' => [
                        'group' => true,
                    ],
                ],
                ParameterBag::ADDITIONAL_PARAMETERS => [
                    'group' => true,
                ],
            ],
            $this->extension->getParameters()->all()
        );
    }

    public function testSetParametersWithoutGroup(): void
    {
        $this->extension->setParameters(new ParameterBag());

        $this->assertEquals([], $this->extension->getParameters()->all());
    }

    public function testProcessConfigs(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 1],
                ]
            );

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(900));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                            'product.sku as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithoutId(): void
    {
        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects($this->never())
            ->method('find');

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                            'product.sku as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountMoreThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 0],
                ]
            );

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(2000));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100, 1000],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                            'product.sku as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountLessThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 0],
                ]
            );

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(999));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                            'product.sku as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithGrouping(): void
    {
        $this->parameters->set('_parameters', ['group' => true]);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'mass_actions' => [
                    'move' => [
                        'label' => 'move.label',
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->never())
            ->method('get');

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT GROUP_CONCAT(innerItem.id ORDER BY innerItem.id ASC) ' .
                            'FROM Oro\Bundle\ShoppingListBundle\Entity\LineItem innerItem ' .
                            'WHERE (innerItem.parentProduct = lineItem.parentProduct ' .
                            'OR innerItem.product = lineItem.product) ' .
                            'AND innerItem.shoppingList = lineItem.shoppingList ' .
                            'AND innerItem.unit = lineItem.unit) as allLineItemsIds',
                            'GROUP_CONCAT(' .
                            'COALESCE(CONCAT(parentProduct.sku, \':\', product.sku), product.sku)' .
                            ') as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
            ],
            $config->toArray()
        );
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $shoppingList = $this->createShoppingList(900);
        $shoppingList->setLabel('Shopping List Label');
        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($shoppingList);

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertEquals(
            [
                'hasEmptyMatrix' => true,
                'canBeGrouped' => true,
                'shoppingListLabel' => 'Shopping List Label',
                'initialState' => [
                    'parameters' => [
                        'group' => false,
                    ],
                ],
                'state' => [
                    'parameters' => [
                        'group' => false,
                    ],
                ],
            ],
            $data->toArray()
        );
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('hasEmptyMatrix'));
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
        $this->assertTrue($data->offsetGetByPath('[metadata][canBeGrouped]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
    }

    private function createShoppingList(int $lineItemsCount): ShoppingList
    {
        $shoppingList = new ShoppingList();

        for ($i = 0; $i < $lineItemsCount; $i++) {
            $shoppingList->addLineItem(new LineItem());
        }

        return $shoppingList;
    }
}
