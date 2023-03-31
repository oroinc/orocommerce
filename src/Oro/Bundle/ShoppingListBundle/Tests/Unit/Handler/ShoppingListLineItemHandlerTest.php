<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListLineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FLUSH_BATCH_SIZE = 100;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ShoppingListLineItemHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new ShoppingListLineItemHandler(
            $this->getDoctrine(),
            $this->shoppingListManager,
            $this->currentShoppingListManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->featureChecker,
            $this->productManager,
            $this->aclHelper
        );
    }

    /**
     * @dataProvider getShoppingListDataProvider
     */
    public function testGetShoppingList(?int $id): void
    {
        $shoppingList = new ShoppingList();

        $this->currentShoppingListManager->expects(self::once())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        self::assertSame($shoppingList, $this->handler->getShoppingList($id));
    }

    public function getShoppingListDataProvider(): array
    {
        return [[1], [null]];
    }

    public function testCreateForShoppingListWithoutPermission(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);

        $this->handler->createForShoppingList(new ShoppingList());
    }

    public function testCreateForShoppingListWithoutUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->handler->createForShoppingList(new ShoppingList());
    }

    public function testCreateForShoppingListForGuestNotAllowed(): void
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('guest_shopping_list')
            ->willReturn(false);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $shoppingList = new ShoppingList();

        self::assertEquals(false, $this->handler->isAllowed($shoppingList));
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(
        bool $isGrantedAdd,
        bool $expected,
        ShoppingList $shoppingList = null,
        bool $isGrantedEdit = false
    ): void {
        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $isGrantedExpectations = [['oro_shopping_list_frontend_update']];
        $isGrantedResults = [$isGrantedAdd];
        if ($shoppingList && $isGrantedAdd) {
            $isGrantedExpectations[] = ['EDIT', $shoppingList];
            $isGrantedResults[] = $isGrantedEdit;
        }
        $this->authorizationChecker->expects(self::exactly(count($isGrantedExpectations)))
            ->method('isGranted')
            ->withConsecutive(...$isGrantedExpectations)
            ->willReturnOnConsecutiveCalls(...$isGrantedResults);

        self::assertEquals($expected, $this->handler->isAllowed($shoppingList));
    }

    public function isAllowedDataProvider(): array
    {
        return [
            [false, false],
            [true, true],
            [false, false, new ShoppingList(), false],
            [false, false, new ShoppingList(), true],
            [true, false, new ShoppingList(), false],
            [true, true, new ShoppingList(), true],
        ];
    }

    /**
     * @dataProvider createForShoppingListDataProvider
     */
    public function testCreateForShoppingList(
        array $productIds = [],
        array $productUnitsWithQuantities = [],
        array $expectedLineItems = []
    ): void {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $customerUser = new CustomerUser();
        $organization = new Organization();

        $shoppingList->expects(self::any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);
        $shoppingList->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->tokenAccessor->expects(self::any())
            ->method('hasUser')
            ->willReturn(true);
        $this->authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->willReturn(true);

        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with(self::isInstanceOf(QueryBuilder::class), [])
            ->willReturnArgument(0);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::exactly(2))
            ->method('markReadOnly');

        $this->entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->shoppingListManager->expects(self::once())
            ->method('bulkAddLineItems')
            ->with(
                self::callback(function (array $lineItems) use ($expectedLineItems, $customerUser, $organization) {
                    /** @var LineItem $lineItem */
                    foreach ($lineItems as $key => $lineItem) {
                        /** @var LineItem $expectedLineItem */
                        $expectedLineItem = $expectedLineItems[$key];

                        $this->assertEquals($expectedLineItem->getQuantity(), $lineItem->getQuantity());
                        $this->assertEquals($customerUser, $lineItem->getCustomerUser());
                        $this->assertEquals($organization, $lineItem->getOrganization());
                        $this->assertInstanceOf(Product::class, $lineItem->getProduct());
                        $this->assertInstanceOf(ProductUnit::class, $lineItem->getUnit());
                    }

                    return true;
                }),
                self::identicalTo($shoppingList),
                self::FLUSH_BATCH_SIZE
            )
            ->willReturn(count($expectedLineItems));

        self::assertEquals(
            count($expectedLineItems),
            $this->handler->createForShoppingList($shoppingList, $productIds, $productUnitsWithQuantities)
        );
    }

    public function createForShoppingListDataProvider(): array
    {
        return [
            'default quantity 1 is set for product with SKU2 as no info in productUnitsWithQuantities provided' => [
                'productIds' => [1, 2],
                'productUnitsWithQuantities' => ['SKU1' => ['item' => 5], 'SKU3' => ['item' => 3]],
                'expectedLineItems' => [(new LineItem())->setQuantity(5), (new LineItem())->setQuantity(1)],
            ],
            'lower case sku is acceptable in productUnitsWithQuantities too' => [
                'productIds' => [1, 2],
                'productUnitsWithQuantities' => ['SKU1' => ['item' => 5], 'sku2абв' => ['item' => 3]],
                'expectedLineItems' => [(new LineItem())->setQuantity(5), (new LineItem())->setQuantity(3)],
            ],
        ];
    }

    public function testPrepareLineItemWithProduct(): void
    {
        $user = $this->createMock(CustomerUser::class);
        $shoppingList = $this->createMock(ShoppingList::class);
        $product = $this->createMock(Product::class);

        $this->currentShoppingListManager->expects(self::once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $item = $this->handler->prepareLineItemWithProduct($user, $product);
        self::assertSame($user, $item->getCustomerUser());
        self::assertSame($shoppingList, $item->getShoppingList());
        self::assertSame($product, $item->getProduct());
    }

    private function getDoctrine(): ManagerRegistry
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::any())
            ->method('toIterable')
            ->willReturn([$this->getProduct(1, 'sku1'), $this->getProduct(2, 'sku2абв')]);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects(self::any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::any())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->entityManager->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(function (string $unit) {
                $entity = new ProductUnit();
                $entity->setCode($unit);

                return $entity;
            });

        $this->entityManager->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        return $doctrine;
    }

    private function getProduct(int $id, string $sku): Product
    {
        $precision = new ProductUnitPrecision();
        $precision->setUnit(new ProductUnit());

        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->setSku($sku);
        $product->setPrimaryUnitPrecision($precision);

        return $product;
    }
}
