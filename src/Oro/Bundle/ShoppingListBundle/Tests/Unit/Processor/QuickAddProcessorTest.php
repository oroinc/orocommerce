<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class QuickAddProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLineItemHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLineItemHandler;

    /** @var ProductMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productMapper;

    /** @var MessageGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $messageGenerator;

    /** @var QuickAddProcessor */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListLineItemHandler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->productMapper = $this->createMock(ProductMapperInterface::class);
        $this->messageGenerator = $this->createMock(MessageGenerator::class);

        $this->processor = new QuickAddProcessor(
            $this->shoppingListLineItemHandler,
            $this->productMapper,
            $this->messageGenerator
        );
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(bool $isAllowed): void
    {
        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        self::assertSame($isAllowed, $this->processor->isAllowed());
    }

    public function isAllowedDataProvider(): array
    {
        return [[false], [true]];
    }

    public function testProcessEmpty(): void
    {
        $data = [];
        $request = new Request();

        $this->shoppingListLineItemHandler->expects(self::never())
            ->method('getShoppingList');

        $this->productMapper->expects(self::never())
            ->method('mapProducts');

        $this->shoppingListLineItemHandler->expects(self::never())
            ->method('createForShoppingList');

        self::assertNull($this->processor->process($data, $request));
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $data,
        Request $request,
        array $productMap,
        array $productIds,
        array $productQuantities,
        bool $failed = false
    ): void {
        // guard
        if ($productMap) {
            self::assertCount(count($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]), $productMap);
        }

        $entitiesCount = count($data);

        $this->shoppingListLineItemHandler->expects(self::any())
            ->method('getShoppingList')
            ->willReturnCallback(function ($shoppingListId) {
                $shoppingList = new ShoppingList();
                if ($shoppingListId) {
                    ReflectionUtil::setId($shoppingList, $shoppingListId);
                }

                return $shoppingList;
            });

        $this->productMapper->expects(self::once())
            ->method('mapProducts')
            ->willReturnCallback(function (ArrayCollection $collection) use ($productMap) {
                foreach ($productMap as $i => $productId) {
                    if (null !== $productId) {
                        $item = $collection[$i];
                        self::assertInstanceOf(\ArrayAccess::class, $item);
                        $item['productId'] = $productId;
                    }
                }
            });

        if ($failed) {
            $this->shoppingListLineItemHandler->expects(self::once())
                ->method('createForShoppingList')
                ->willThrowException(new AccessDeniedException());
        } else {
            $this->shoppingListLineItemHandler->expects($data ? self::once() : self::never())
                ->method('createForShoppingList')
                ->with(
                    self::isInstanceOf(ShoppingList::class),
                    array_values($productIds),
                    $productQuantities
                )
                ->willReturn($entitiesCount);
        }

        $flashBag = $this->createMock(FlashBagInterface::class);
        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $request->setSession($session);

        $message = 'test message';
        if ($failed) {
            $this->messageGenerator->expects(self::once())
                ->method('getFailedMessage')
                ->willReturn($message);
            $flashBag->expects(self::once())
                ->method('add')
                ->with('error', $message)
                ->willReturnSelf();
        } elseif ($entitiesCount) {
            $this->messageGenerator->expects(self::once())
                ->method('getSuccessMessage')
                ->willReturn($message);
            $flashBag->expects(self::once())
                ->method('add')
                ->with('success', $message)
                ->willReturnSelf();
        }

        self::assertNull($this->processor->process($data, $request));
    }

    public function processDataProvider(): array
    {
        return [
            'new shopping list' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'kg'],
                    ]
                ],
                new Request(),
                [1, 2],
                [1, 2],
                [1 => ['item' => 2], 2 => ['kg' => 3]]
            ],
            'shopping list with same products couple of times' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku1Абв', 'productQuantity' => 3, 'productUnit' => 'kg'],
                        ['productSku' => 'sku1абВ', 'productQuantity' => 4, 'productUnit' => 'set'],
                        ['productSku' => 'sku1абв', 'productQuantity' => 5, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 5, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 6, 'productUnit' => 'kg'],
                    ]
                ],
                new Request(),
                [1, 1, 1, 1, 2, 2],
                [1, 2],
                [1 => ['item' => 7, 'kg' => 3, 'set' => 4], 2 => ['item' => 5, 'kg' => 6]]
            ],
            'shopping list with products skus in descending order' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 11, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 12, 'productUnit' => 'kg'],
                    ]
                ],
                new Request(),
                [1, 1],
                [1],
                [1 => ['item' => 11, 'kg' => 12]]
            ],
            'existing shopping list' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                [1, 2],
                [1, 2],
                [1 => ['item' => 2], 2 => ['kg' => 3]]
            ],
            'ids sorting' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'item'],
                        ['productSku' => 'SKU1', 'productQuantity' => 2, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                [2, 1],
                [2, 1],
                [2 => ['item' => 3], 1 => ['kg' => 2]]
            ],
            'process failed' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(),
                [1, 2],
                [1, 2],
                [1 => ['kg' => 2], 2 => ['item' => 3]],
                true
            ],
        ];
    }
}
