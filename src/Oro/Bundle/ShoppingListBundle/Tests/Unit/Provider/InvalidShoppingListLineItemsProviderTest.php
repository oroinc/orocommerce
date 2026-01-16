<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class InvalidShoppingListLineItemsProviderTest extends TestCase
{
    use EntityTrait;

    private ValidatorInterface&MockObject $validator;
    private ProductLineItemsHolderFactoryInterface&MockObject $lineItemsHolderFactory;
    private ShoppingListInvalidLineItemsMapper&MockObject $shoppingListInvalidLineItemsMapper;
    private ShoppingListValidationGroupsProvider&MockObject $validationGroupsProvider;
    private PreloadingManager&MockObject $preloadingManager;

    private InvalidShoppingListLineItemsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->lineItemsHolderFactory = $this->createMock(ProductLineItemsHolderFactoryInterface::class);
        $this->shoppingListInvalidLineItemsMapper = $this->createMock(ShoppingListInvalidLineItemsMapper::class);
        $this->validationGroupsProvider = $this->createMock(ShoppingListValidationGroupsProvider::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->provider = new InvalidShoppingListLineItemsProvider(
            $this->validator,
            $this->lineItemsHolderFactory,
            $this->shoppingListInvalidLineItemsMapper,
            $this->validationGroupsProvider,
            $this->preloadingManager
        );
    }

    public function testGetInvalidLineItemsIdsBySeverityWithEmptyLineItems(): void
    {
        self::assertSame([
            InvalidShoppingListLineItemsProvider::ERRORS => [],
            InvalidShoppingListLineItemsProvider::WARNINGS => [],
        ], $this->provider->getInvalidLineItemsIdsBySeverity(new ArrayCollection([])));
    }

    public function testGetInvalidLineItemsIdsWithEmptyLineItems(): void
    {
        self::assertSame([], $this->provider->getInvalidLineItemsIds(new ArrayCollection([])));
    }

    public function testGetResultWhenNoApplicableValidationGroups(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem($this->getEntity(LineItem::class, ['id' => 2]));

        $this->lineItemsHolderFactory->expects(self::never())
            ->method('createFromLineItems');

        $this->validator->expects(self::never())
            ->method('validate');

        $this->shoppingListInvalidLineItemsMapper->expects(self::never())
            ->method('mapViolationListBySeverity');

        $result = $this->provider->getInvalidLineItemsIdsBySeverity($shoppingList->getLineItems());

        self::assertSame([
            InvalidShoppingListLineItemsProvider::ERRORS => [],
            InvalidShoppingListLineItemsProvider::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidLineItemsIdsBySeverityWhenResultAlreadyCached(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem($this->getEntity(LineItem::class, ['id' => 2]));

        $lineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);

        $this->validationGroupsProvider->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['group1']);

        $this->preloadingManager->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                self::isType('array'),
                self::equalTo([
                    'product' => [
                        'unitPrecisions' => [],
                        'names' => [],
                        'manageInventory' => [],
                        'decrementQuantity' => [],
                        'backOrder' => [],
                        'inventoryThreshold' => [],
                        'highlightLowInventory' => [],
                        'isUpcoming' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                        'category' => [
                            'highlightLowInventory' => [],
                            'isUpcoming' => [],
                            'maximumQuantityToOrder' => [],
                            'minimumQuantityToOrder' => [],
                        ],
                    ],
                ])
            );

        $this->lineItemsHolderFactory->expects(self::once())
            ->method('createFromLineItems')
            ->willReturn($lineItemsHolder);

        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->shoppingListInvalidLineItemsMapper->expects(self::once())
            ->method('mapViolationListBySeverity')
            ->willReturn([
                InvalidShoppingListLineItemsProvider::ERRORS => [
                    2 => [],
                    1 => [],
                ],
                InvalidShoppingListLineItemsProvider::WARNINGS => []
            ]);

        $result1 = $this->provider->getInvalidLineItemsIdsBySeverity($shoppingList->getLineItems());
        self::assertSame([
            InvalidShoppingListLineItemsProvider::ERRORS => [2, 1],
            InvalidShoppingListLineItemsProvider::WARNINGS => [],
        ], $result1);

        $result2 = $this->provider->getInvalidLineItemsIdsBySeverity($shoppingList->getLineItems());
        self::assertSame([
            InvalidShoppingListLineItemsProvider::ERRORS => [2, 1],
            InvalidShoppingListLineItemsProvider::WARNINGS => [],
        ], $result2);
    }

    public function testGetInvalidLineItemsIdsWhenResultAlreadyCached(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem($this->getEntity(LineItem::class, ['id' => 2]));

        $lineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);

        $this->validationGroupsProvider->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['group1']);

        $this->preloadingManager->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                self::isType('array'),
                self::equalTo([
                    'product' => [
                        'unitPrecisions' => [],
                        'names' => [],
                        'manageInventory' => [],
                        'decrementQuantity' => [],
                        'backOrder' => [],
                        'inventoryThreshold' => [],
                        'highlightLowInventory' => [],
                        'isUpcoming' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                        'category' => [
                            'highlightLowInventory' => [],
                            'isUpcoming' => [],
                            'maximumQuantityToOrder' => [],
                            'minimumQuantityToOrder' => [],
                        ],
                    ],
                ])
            );

        $this->lineItemsHolderFactory->expects(self::once())
            ->method('createFromLineItems')
            ->willReturn($lineItemsHolder);

        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->shoppingListInvalidLineItemsMapper->expects(self::once())
            ->method('mapViolationListBySeverity')
            ->willReturn([
                InvalidShoppingListLineItemsProvider::ERRORS => [
                    2 => [],
                    1 => [],
                ],
                InvalidShoppingListLineItemsProvider::WARNINGS => []
            ]);

        $result1 = $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems());
        self::assertSame([2, 1], $result1);

        $result2 = $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems());
        self::assertSame([2, 1], $result2);
    }

    public function testGetInvalidItemsViolations(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);

        $lineItem1 = new LineItem();
        $shoppingList->addLineItem($lineItem1);

        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 2]);
        $kitItemLineItem1 = $this->getEntity(ProductKitItemLineItem::class, ['id' => 10]);
        $kitItemLineItem2 = $this->getEntity(ProductKitItemLineItem::class, ['id' => 11]);
        $kitItemLineItems = new ArrayCollection([$kitItemLineItem1, $kitItemLineItem2]);
        $this->setValue($lineItem2, 'kitItemLineItems', $kitItemLineItems);

        $shoppingList->addLineItem($lineItem2);

        $lineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);

        $this->preloadingManager->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                self::isType('array'),
                self::equalTo([
                    'product' => [
                        'unitPrecisions' => [],
                        'names' => [],
                        'manageInventory' => [],
                        'decrementQuantity' => [],
                        'backOrder' => [],
                        'inventoryThreshold' => [],
                        'highlightLowInventory' => [],
                        'isUpcoming' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                        'category' => [
                            'highlightLowInventory' => [],
                            'isUpcoming' => [],
                            'maximumQuantityToOrder' => [],
                            'minimumQuantityToOrder' => [],
                        ],
                    ],
                ])
            );

        $this->lineItemsHolderFactory->expects(self::once())
            ->method('createFromLineItems')
            ->with(new ArrayCollection([$lineItem1, $lineItem2]))
            ->willReturn($lineItemsHolder);

        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $lineItemsHolder,
                null,
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn(new ConstraintViolationList());

        $expectedResult = [
            InvalidShoppingListLineItemsProvider::ERRORS => [2 => [], 5 => []],
            InvalidShoppingListLineItemsProvider::WARNINGS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => ['General Message'],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => [
                        2 => [ShoppingListInvalidLineItemsMapper::MESSAGES => ['Kit Item Message']]
                    ]
                ]
            ]
        ];

        $this->shoppingListInvalidLineItemsMapper->expects(self::once())
            ->method('mapViolationListBySeverity')
            ->with(
                new ConstraintViolationList(),
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn($expectedResult);

        $result = $this->provider->getInvalidItemsViolations($shoppingList->getLineItems());
        self::assertSame($expectedResult, $result);
    }

    public function testGetInvalidItemsViolationsWithSpecificValidationGroupType(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem(new LineItem());

        $lineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);

        $this->preloadingManager->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                self::isType('array'),
                self::equalTo([
                    'product' => [
                        'unitPrecisions' => [],
                        'names' => [],
                        'manageInventory' => [],
                        'decrementQuantity' => [],
                        'backOrder' => [],
                        'inventoryThreshold' => [],
                        'highlightLowInventory' => [],
                        'isUpcoming' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                        'category' => [
                            'highlightLowInventory' => [],
                            'isUpcoming' => [],
                            'maximumQuantityToOrder' => [],
                            'minimumQuantityToOrder' => [],
                        ],
                    ],
                ])
            );

        $this->lineItemsHolderFactory->expects(self::once())
            ->method('createFromLineItems')
            ->with($shoppingList->getLineItems())
            ->willReturn($lineItemsHolder);

        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupByType')
            ->willReturn('datagrid_line_items_data_for_checkout');

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, ['datagrid_line_items_data_for_checkout'])
            ->willReturn(new ConstraintViolationList());

        $expectedResult = [
            InvalidShoppingListLineItemsProvider::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => ['General Message'],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => [
                        2 => [ShoppingListInvalidLineItemsMapper::MESSAGES => ['Kit Item Message']]
                    ]
                ]
            ],
            InvalidShoppingListLineItemsProvider::WARNINGS => []
        ];

        $this->shoppingListInvalidLineItemsMapper->expects(self::once())
            ->method('mapViolationListBySeverity')
            ->with(new ConstraintViolationList(), ['datagrid_line_items_data_for_checkout'])
            ->willReturn($expectedResult);

        $result = $this->provider->getInvalidItemsViolations($shoppingList->getLineItems(), 'checkout');
        self::assertSame($expectedResult, $result);
    }

    public function testReset(): void
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem(new LineItem());

        $lineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);

        $this->validationGroupsProvider->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['group1']);

        $this->preloadingManager->expects(self::exactly(2))
            ->method('preloadInEntities')
            ->with(
                self::isType('array'),
                self::equalTo([
                    'product' => [
                        'unitPrecisions' => [],
                        'names' => [],
                        'manageInventory' => [],
                        'decrementQuantity' => [],
                        'backOrder' => [],
                        'inventoryThreshold' => [],
                        'highlightLowInventory' => [],
                        'isUpcoming' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                        'category' => [
                            'highlightLowInventory' => [],
                            'isUpcoming' => [],
                            'maximumQuantityToOrder' => [],
                            'minimumQuantityToOrder' => [],
                        ],
                    ],
                ])
            );

        $this->lineItemsHolderFactory->expects(self::exactly(2))
            ->method('createFromLineItems')
            ->willReturn($lineItemsHolder);

        $this->validator->expects(self::exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->shoppingListInvalidLineItemsMapper->expects(self::exactly(2))
            ->method('mapViolationListBySeverity')
            ->willReturn([]);

        $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems());

        $this->provider->reset();

        $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems());
    }
}
