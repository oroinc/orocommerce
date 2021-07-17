<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShoppingListLineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShoppingListLineItemHandler */
    protected $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListManager */
    protected $shoppingListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager */
    protected $currentShoppingListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    protected $featureChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductManager */
    protected $productManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->managerRegistry = $this->getManagerRegistry();
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->handler = new ShoppingListLineItemHandler(
            $this->managerRegistry,
            $this->shoppingListManager,
            $this->currentShoppingListManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->featureChecker,
            $this->productManager,
            $this->aclHelper
        );
        $this->handler->setProductClass(Product::class);
        $this->handler->setShoppingListClass(ShoppingList::class);
        $this->handler->setProductUnitClass(ProductUnit::class);
    }

    /**
     * @dataProvider idDataProvider
     * @param mixed $id
     */
    public function testGetShoppingList($id)
    {
        $shoppingList = new ShoppingList();
        $this->currentShoppingListManager->expects($this->once())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);
        $this->assertSame($shoppingList, $this->handler->getShoppingList($id));
    }

    /**
     * @return array
     */
    public function idDataProvider()
    {
        return [[1], [null]];
    }

    public function testCreateForShoppingListWithoutPermission()
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->handler->createForShoppingList(new ShoppingList());
    }

    public function testCreateForShoppingListWithoutUser()
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->handler->createForShoppingList(new ShoppingList());
    }

    public function testCreateForShoppingListForGuestNotAllowed()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_shopping_list')
            ->will($this->returnValue(false));

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $shoppingList = new ShoppingList();

        $this->assertEquals(false, $this->handler->isAllowed($shoppingList));
    }

    /**
     * @param bool $isGrantedAdd
     * @param bool $expected
     * @param bool $isGrantedEdit
     * @param ShoppingList|null $shoppingList
     *
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed($isGrantedAdd, $expected, ShoppingList $shoppingList = null, $isGrantedEdit = false)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update')
            ->willReturn($isGrantedAdd);

        if ($shoppingList && $isGrantedAdd) {
            $this->authorizationChecker->expects($this->at(1))
                ->method('isGranted')
                ->with('EDIT', $shoppingList)
                ->willReturn($isGrantedEdit);
        }

        $this->assertEquals($expected, $this->handler->isAllowed($shoppingList));
    }

    /** @return array */
    public function isAllowedDataProvider()
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
     * @dataProvider itemDataProvider
     */
    public function testCreateForShoppingList(
        array $productIds = [],
        array $productUnitsWithQuantities = [],
        array $expectedLineItems = []
    ) {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $customerUser = new CustomerUser();
        $organization = new Organization();

        $shoppingList->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);
        $shoppingList->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->tokenAccessor->expects($this->any())
            ->method('hasUser')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $this->productManager
            ->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($this->isInstanceOf(QueryBuilder::class), [])
            ->willReturnArgument(0);

        $this->shoppingListManager->expects($this->once())->method('bulkAddLineItems')->with(
            $this->callback(
                function (array $lineItems) use ($expectedLineItems, $customerUser, $organization) {
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
                }
            ),
            $shoppingList,
            $this->isType('integer')
        );

        $this->handler->createForShoppingList($shoppingList, $productIds, $productUnitsWithQuantities);
    }

    /**
     * @return array
     */
    public function itemDataProvider()
    {
        return [
            'default quantity 1 is set for product with SKU2 as no info in productUnitsWithQuantities provided' => [
                'productIds' => [1, 2],
                'productUnitsWithQuantities' => ['SKU1' => ['item' => 5], 'SKU3' => ['item' => 3]],
                'expectedLineItems' => [(new LineItem())->setQuantity(5), (new LineItem())->setQuantity(1)]
            ],
            'lower case sku is acceptable in productUnitsWithQuantities too' => [
                'productIds' => [1, 2],
                'productUnitsWithQuantities' => ['SKU1' => ['item' => 5], 'sku2абв' => ['item' => 3]],
                'expectedLineItems' => [(new LineItem())->setQuantity(5), (new LineItem())->setQuantity(3)]
            ]
        ];
    }

    public function testPrepareLineItemWithProduct()
    {
        /** @var CustomerUser $user */
        $user = $this->createMock(CustomerUser::class);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->createMock(ShoppingList::class);

        /** @var Product $product */
        $product = $this->createMock(Product::class);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $item = $this->handler->prepareLineItemWithProduct($user, $product);
        $this->assertSame($user, $item->getCustomerUser());
        $this->assertSame($shoppingList, $item->getShoppingList());
        $this->assertSame($product, $item->getProduct());
    }

    /**
     * @return Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getManagerRegistry()
    {
        $em = $this->createMock(EntityManager::class);

        /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['iterate'])
            ->getMockForAbstractClass();

        $product1 = $this->getEntity(Product::class, [
            'id' => 1,
            'sku' => 'sku1',
            'skuUppercase' => 'SKU1',
            'primaryUnitPrecision' => (new ProductUnitPrecision())->setUnit(new ProductUnit())
        ]);

        $product2 = $this->getEntity(Product::class, [
            'id' => 2,
            'sku' => 'sku2абв',
            'skuUppercase' => 'SKU2АБВ',
            'primaryUnitPrecision' => (new ProductUnitPrecision())->setUnit(new ProductUnit())
        ]);

        $iterableResult = [[$product1], [$product2]];
        $query->expects($this->any())
            ->method('iterate')
            ->willReturn($iterableResult);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $productRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductsQueryBuilder', 'findOneBy'])
            ->getMock();

        $productRepository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->any())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $shoppingListRepository = $this->createMock(EntityRepository::class);
        $productUnitRepository = $this->createMock(EntityRepository::class);

        $productUnitRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(function ($unit) {
                return $this->getEntity(ProductUnit::class, ['code' => $unit]);
            });

        $em->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [ShoppingList::class, $shoppingListRepository],
                        [Product::class, $productRepository],
                        [ProductUnit::class, $productUnitRepository],
                    ]
                )
            );

        $em->expects($this->any())->method('getReference')->will(
            $this->returnCallback(
                function ($className, $id) {
                    return $this->getEntity($className, ['id' => $id]);
                }
            )
        );

        $managerRegistry = $this->createMock(Registry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $managerRegistry;
    }
}
