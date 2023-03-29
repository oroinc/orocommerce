<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
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
    private $handler;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var MessageGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $messageGenerator;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var QuickAddProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->messageGenerator = $this->createMock(MessageGenerator::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->processor = new QuickAddProcessor(
            $this->handler,
            $doctrine,
            $this->aclHelper,
            $this->messageGenerator
        );
    }
    public function testGetName(): void
    {
        self::assertEquals('oro_shopping_list_quick_add_processor', $this->processor->getName());
    }

    public function testIsValidationRequired(): void
    {
        self::assertTrue($this->processor->isValidationRequired());
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(bool $isAllowed): void
    {
        $this->handler->expects(self::once())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        self::assertSame($isAllowed, $this->processor->isAllowed());
    }

    public function isAllowedDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $data,
        Request $request,
        array $productIds = [],
        array $productQuantities = [],
        bool $failed = false
    ): void {
        $entitiesCount = count($data);

        $this->handler->expects(self::any())
            ->method('getShoppingList')
            ->willReturnCallback(function ($shoppingListId) {
                $shoppingList = new ShoppingList();
                if ($shoppingListId) {
                    ReflectionUtil::setId($shoppingList, $shoppingListId);
                }

                return $shoppingList;
            });

        $result = [];
        foreach ($productIds as $sku => $id) {
            $result[] = ['id' => $id, 'sku' => $sku];
        }
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($result);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(array_column($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY], 'productSku'))
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        if ($failed) {
            $this->handler->expects(self::once())
                ->method('createForShoppingList')
                ->willThrowException(new AccessDeniedException());
        } else {
            $this->handler->expects($data ? self::once() : self::never())
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

    public function testProcessEmpty(): void
    {
        $data = [];
        $request = new Request();

        $this->handler->expects(self::never())
            ->method('getShoppingList');

        $this->productRepository->expects(self::never())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with([]);

        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->handler->expects(self::never())
            ->method('createForShoppingList');

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
                ['sku1' => 1, 'sku2' => 2],
                ['SKU1' => ['item' => 2], 'SKU2' => ['kg' => 3]]
            ],
            'shopping list with same products couple of times' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku1Абв', 'productQuantity' => 3, 'productUnit' => 'kg'],
                        ['productSku' => 'sku1абВ', 'productQuantity' => 4, 'productUnit' => 'set'],
                        ['productSku' => 'sku2', 'productQuantity' => 5, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 6, 'productUnit' => 'kg'],
                    ]
                ],
                new Request(),
                ['sku1абв' => 1, 'sku2' => 2],
                ['SKU1АБВ' => ['item' => 2, 'kg' => 3, 'set' => 4], 'SKU2' => ['item' => 5, 'kg' => 6]]
            ],
            'shopping list with products skus in descending order' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 11, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 12, 'productUnit' => 'kg'],
                    ]
                ],
                new Request(),
                [],
                ['SKU2' => ['kg' => 12], 'SKU1' => ['item' => 11]]
            ],
            'existing shopping list' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                ['sku1' => 1, 'sku2' => 2],
                ['SKU1' => ['item' => 2], 'SKU2' => ['kg' => 3]]
            ],
            'ids sorting' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'item'],
                        ['productSku' => 'SKU1', 'productQuantity' => 2, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                ['sku2' => 2, 'sku1' => 1],
                ['SKU1' => ['kg' => 2], 'SKU2' => ['item' => 3]]
            ],
            'process failed' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'item'],
                        ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'kg'],
                    ],
                ],
                new Request(),
                ['sku1' => 1, 'sku2' => 2],
                ['SKU1' => ['kg' => 2], 'SKU2' => ['item' => 3]],
                true
            ],
        ];
    }
}
