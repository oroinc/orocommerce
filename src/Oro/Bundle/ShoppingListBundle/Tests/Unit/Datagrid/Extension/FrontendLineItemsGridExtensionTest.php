<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class FrontendLineItemsGridExtensionTest extends TestCase
{
    private ShoppingListRepository&MockObject $shoppingListRepository;
    private LineItemRepository&MockObject $lineItemRepository;
    private ConfigManager&MockObject $configManager;
    private TokenAccessorInterface&MockObject $tokenAccessor;

    private ParameterBag $parameters;
    private FrontendLineItemsGridExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $registry->expects(self::any())
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                return match ($class) {
                    ShoppingList::class => $this->shoppingListRepository,
                    LineItem::class     => $this->lineItemRepository,
                    default             => null,
                };
            });

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->parameters = new ParameterBag();

        $this->extension = new FrontendLineItemsGridExtension($registry, $this->configManager, $this->tokenAccessor);
        $this->extension->setParameters($this->parameters);
        $this->extension->setSupportedGrids([
            'frontend-customer-user-shopping-list-grid',
            'frontend-customer-user-shopping-list-edit-grid',
            'frontend-customer-user-shopping-list-saved-for-later-edit-grid',
        ]);

        $this->extension->setSavedForLaterGrids([
            'frontend-customer-user-shopping-list-saved-for-later-edit-grid',
        ]);
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

        self::assertEquals(
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

        self::assertEquals([], $this->extension->getParameters()->all());
    }

    public function testProcessConfigs(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'name' => 'test-grid',
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

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 1],
                ]
            );

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(900));

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                'name' => 'test-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsForSavedForLaterGrid(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'name' => 'frontend-customer-user-shopping-list-saved-for-later-edit-grid',
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

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 1],
                ]
            );

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(2, true));

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                1
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
                'name' => 'frontend-customer-user-shopping-list-saved-for-later-edit-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithoutId(): void
    {
        $config = DatagridConfiguration::create(
            [
                'name' => 'test-grid',
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

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects(self::never())
            ->method('find');

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                'name' => 'test-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountMoreThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'name' => 'test-grid',
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

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 0],
                ]
            );

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(2000));

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                'name' => 'test-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountLessThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'name' => 'test-grid',
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

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_shopping_list.shopping_lists_max_line_items_per_page', false, false, null, 1000],
                    ['oro_shopping_list.shopping_list_limit', false, false, null, 0],
                ]
            );

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($this->createShoppingList(999));

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                'name' => 'test-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithGrouping(): void
    {
        $this->parameters->set('_parameters', ['group' => true]);

        $config = DatagridConfiguration::create(
            [
                'name' => 'test-grid',
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

        $this->configManager->expects(self::never())
            ->method('get');

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                            'WHERE (' .
                            '  innerItem.parentProduct = lineItem.parentProduct OR ' .
                            '  (innerItem.product = lineItem.product AND innerItem.checksum = lineItem.checksum)' .
                            ') ' .
                            'AND innerItem.shoppingList = lineItem.shoppingList ' .
                            'AND innerItem.unit = lineItem.unit) as allLineItemsIds',
                            'GROUP_CONCAT(' .
                            '  COALESCE(CONCAT(parentProduct.sku, \':\', product.sku), product.sku)' .
                            ') as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
                'name' => 'test-grid'
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithGroupingForSavedForLaterGrid(): void
    {
        $this->parameters->set('_parameters', ['group' => true]);

        $config = DatagridConfiguration::create(
            [
                'name' => 'frontend-customer-user-shopping-list-saved-for-later-edit-grid',
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

        $this->configManager->expects(self::never())
            ->method('get');

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $this->extension->processConfigs($config);

        self::assertEquals(
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
                            'WHERE (' .
                            '  innerItem.parentProduct = lineItem.parentProduct OR ' .
                            '  (innerItem.product = lineItem.product AND innerItem.checksum = lineItem.checksum)' .
                            ') ' .
                            'AND innerItem.savedForLaterList = lineItem.savedForLaterList ' .
                            'AND innerItem.unit = lineItem.unit) as allLineItemsIds',
                            'GROUP_CONCAT(' .
                            '  COALESCE(CONCAT(parentProduct.sku, \':\', product.sku), product.sku)' .
                            ') as sortSku',
                        ],
                    ],
                ],
                'mass_actions' => [],
                'name' => 'frontend-customer-user-shopping-list-saved-for-later-edit-grid',
            ],
            $config->toArray()
        );
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = MetadataObject::create(['name' => 'test-grid']);

        $this->lineItemRepository->expects(self::once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects(self::once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $shoppingList = $this->createShoppingList(900);
        $shoppingList->setLabel('Shopping List Label');
        $this->shoppingListRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($shoppingList);

        $this->extension->visitMetadata(DatagridConfiguration::create(['name' => 'test-grid']), $data);

        self::assertEquals(
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
                'name' => 'test-grid'
            ],
            $data->toArray()
        );
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects(self::never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects(self::never())
            ->method('canBeGrouped');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        self::assertNull($data->offsetGetByPath('hasEmptyMatrix'));
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = ResultsObject::create(['name' => 'test-grid']);

        $this->lineItemRepository->expects(self::once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects(self::once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create(['name' => 'test-grid']), $data);

        self::assertTrue($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
        self::assertTrue($data->offsetGetByPath('[metadata][canBeGrouped]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects(self::never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects(self::never())
            ->method('canBeGrouped');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        self::assertNull($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
    }

    private function createShoppingList(int $lineItemsCount, bool $savedForLater = false): ShoppingList
    {
        $shoppingList = new ShoppingList();

        for ($i = 0; $i < $lineItemsCount; $i++) {
            if ($savedForLater) {
                $shoppingList->addSavedForLaterLineItem(new LineItem());
            } else {
                $shoppingList->addLineItem(new LineItem());
            }
        }

        return $shoppingList;
    }
}
