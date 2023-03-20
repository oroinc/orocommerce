<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListManagerTest extends TestCase
{
    private ShoppingListManager $manager;

    private TokenAccessorInterface|MockObject $tokenAccessor;

    private WebsiteManager|MockObject $websiteManager;

    private ShoppingListTotalManager|MockObject $totalManager;

    private ProductVariantAvailabilityProvider|MockObject $productVariantProvider;

    private EntityManager|MockObject $em;

    private LineItemRepository|MockObject $lineItemRepository;

    private ConfigManager|MockObject $configManager;

    private EntityDeleteHandlerRegistry|MockObject $deleteHandlerRegistry;

    private LineItemChecksumGeneratorInterface|MockObject $lineItemChecksumGenerator;

    protected function setUp(): void
    {
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->lineItemRepository
            ->expects(self::any())
            ->method('findDuplicateInShoppingList')
            ->willReturnCallback(function (LineItem $lineItem, ShoppingList $shoppingList) {
                /** @var ArrayCollection $shoppingListLineItems */
                $shoppingListLineItems = $shoppingList->getLineItems();
                if ($shoppingList->getId() === 1
                    && $shoppingListLineItems->count() > 0
                    && $shoppingListLineItems->current()->getUnit() === $lineItem->getUnit()
                ) {
                    return $shoppingList->getLineItems()->current();
                }

                return null;
            });

        $this->em = $this->createMock(EntityManager::class);
        $this->em->expects(self::any())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->lineItemRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->totalManager = $this->createMock(ShoppingListTotalManager::class);
        $this->productVariantProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $roundingService = $this->createMock(QuantityRoundingService::class);
        $roundingService->expects(self::any())
            ->method('roundQuantity')
            ->willReturnCallback(function ($value) {
                return round($value);
            });

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->deleteHandlerRegistry = $this->createMock(EntityDeleteHandlerRegistry::class);
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->manager = new ShoppingListManager(
            $doctrine,
            $this->tokenAccessor,
            $translator,
            $roundingService,
            $this->websiteManager,
            $this->totalManager,
            $this->productVariantProvider,
            $this->configManager,
            $this->deleteHandlerRegistry,
            $this->lineItemChecksumGenerator
        );
    }

    private function getShoppingList(int $id, bool $isCurrent = false): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        $shoppingList->setCurrent($isCurrent);

        return $shoppingList;
    }

    private function getLineItem(int $id): LineItem
    {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);

        return $lineItem;
    }

    private function getProduct(int $id, string $type = null): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        if (null !== $type) {
            $product->setType($type);
        }

        return $product;
    }

    private function getProductUnit(string $code, int $defaultPrecision): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);
        $productUnit->setDefaultPrecision($defaultPrecision);

        return $productUnit;
    }

    /**
     * @param LineItem[] $lineItems
     * @param bool $flush
     */
    private function assertDeleteLineItems(array $lineItems, bool $flush = true): void
    {
        if ($lineItems) {
            $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);
            $this->deleteHandlerRegistry->expects(self::once())
                ->method('getHandler')
                ->with(LineItem::class)
                ->willReturn($deleteHandler);
            $deleteExpectations = [];
            $deletedRecords = [];
            foreach ($lineItems as $lineItem) {
                $deleteExpectations[] = [self::identicalTo($lineItem), self::isFalse()];
                $deletedRecords[] = ['entity' => $lineItem];
            }
            $deleteHandler->expects(self::exactly(count($deleteExpectations)))
                ->method('delete')
                ->withConsecutive(...$deleteExpectations)
                ->willReturnOnConsecutiveCalls(...$deletedRecords);
            if ($flush) {
                $deleteHandler->expects(self::once())
                    ->method('flushAll')
                    ->with($deletedRecords);
            } else {
                $deleteHandler->expects(self::never())
                    ->method('flushAll');
            }
        } else {
            $this->deleteHandlerRegistry->expects(self::never())
                ->method('getHandler')
                ->with(LineItem::class);
        }
    }

    public function testCreate(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $shoppingList = $this->manager->create();

        self::assertSame($customerUser, $shoppingList->getCustomerUser());
        self::assertSame($customerUser->getCustomer(), $shoppingList->getCustomer());
        self::assertSame($customerUser->getOrganization(), $shoppingList->getOrganization());
        self::assertSame($website, $shoppingList->getWebsite());
    }

    public function testCreateWithCustomerUserInParameters(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());
        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $shoppingList = $this->manager->create(false, '', $customerUser);

        self::assertSame($customerUser, $shoppingList->getCustomerUser());
        self::assertSame($customerUser->getCustomer(), $shoppingList->getCustomer());
        self::assertSame($customerUser->getOrganization(), $shoppingList->getOrganization());
        self::assertSame($website, $shoppingList->getWebsite());
    }

    public function testCreateWithFlushAndLabel(): void
    {
        $label = 'test label';

        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(ShoppingList::class));
        $this->em->expects(self::once())
            ->method('flush');

        $shoppingList = $this->manager->create(true, $label);

        self::assertEquals($label, $shoppingList->getLabel());
        self::assertSame($customerUser, $shoppingList->getCustomerUser());
        self::assertSame($customerUser->getCustomer(), $shoppingList->getCustomer());
        self::assertSame($customerUser->getOrganization(), $shoppingList->getOrganization());
        self::assertSame($website, $shoppingList->getWebsite());
    }

    public function testCreateWhenNoCustomerUser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The customer user does not exist in the security context.');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->manager->create();
    }

    /**
     * @dataProvider addLineItemDataProvider
     */
    public function testAddLineItem(LineItem $lineItem): void
    {
        $shoppingList = new ShoppingList();

        $this->em
            ->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([$lineItem], [$shoppingList]);

        $this->manager->addLineItem($lineItem, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertNull($lineItem->getCustomerUser());
        self::assertNull($lineItem->getOrganization());
    }

    public function addLineItemDataProvider(): array
    {
        $configurableLineItem = new LineItem();
        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);
        $configurableLineItem->setProduct($configurableProduct);
        $configurableLineItem->setQuantity(0);

        return [
            'empty line item' => [
                'lineItem' => new LineItem(),
            ],
            'empty configurable product' => [
                'lineItem' => $configurableLineItem,
            ],
        ];
    }

    public function testAddLineItemWithChecksum(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        $checksum = 'sample_checksum';
        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        $this->manager->addLineItem($lineItem, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals($checksum, $lineItem->getChecksum());
    }

    public function testAddLineItemWithShoppingListData(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser(new CustomerUser());
        $shoppingList->setOrganization(new Organization());
        $lineItem = new LineItem();

        $this->manager->addLineItem($lineItem, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertSame($shoppingList->getCustomerUser(), $lineItem->getCustomerUser());
        self::assertSame($shoppingList->getOrganization(), $lineItem->getOrganization());
    }

    public function testAddLineItemDuplicate(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $this->em->expects(self::exactly(2))
            ->method('flush');

        $lineItem = (new LineItem())
            ->setUnit($this->getProductUnit('test', 1))
            ->setQuantity(10);

        $this->manager->addLineItem($lineItem, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->addLineItem($lineItemDuplicate, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $resultingItem */
        $resultingItem = $shoppingList->getLineItems()->first();
        self::assertEquals(15, $resultingItem->getQuantity());
    }

    public function testAddLineItemDuplicateAndConcatNotes(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $this->em->expects(self::exactly(2))
            ->method('flush');

        $lineItem = (new LineItem())
            ->setUnit($this->getProductUnit('test', 1))
            ->setNotes('Notes');

        $this->manager->addLineItem($lineItem, $shoppingList);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setNotes('Duplicated Notes');

        $this->manager->addLineItem($lineItemDuplicate, $shoppingList, true, true);

        self::assertCount(1, $shoppingList->getLineItems());

        /** @var LineItem $resultingItem */
        $resultingItem = $shoppingList->getLineItems()->first();
        self::assertSame('Notes Duplicated Notes', $resultingItem->getNotes());
    }

    public function testGetLineItemExistingItem(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem = $this->getLineItem(1);
        $lineItem->setNotes('123');
        $this->manager->addLineItem($lineItem, $shoppingList);
        $returnedLineItem = $this->manager->getLineItem(1, $shoppingList);
        self::assertEquals($returnedLineItem->getNotes(), $lineItem->getNotes());
    }

    public function testGetLineItemNotExistingItem(): void
    {
        $shoppingList = new ShoppingList();
        $returnedLineItem = $this->manager->getLineItem(1, $shoppingList);
        self::assertNull($returnedLineItem);
    }

    /**
     * @dataProvider removeProductDataProvider
     */
    public function testRemoveProduct(array $lineItems, array $relatedLineItems, bool $flush, bool $expectedFlush): void
    {
        $shoppingList = $this->getShoppingList(1, true);
        foreach ($lineItems as $lineItem) {
            $shoppingList->addLineItem($lineItem);
        }

        $product = $this->getProduct(42);

        $this->lineItemRepository->expects(self::once())
            ->method('getItemsByShoppingListAndProducts')
            ->with($shoppingList, [$product])
            ->willReturn($relatedLineItems);

        $this->assertDeleteLineItems($relatedLineItems, $expectedFlush);

        $result = $this->manager->removeProduct($shoppingList, $product, $flush);

        self::assertEquals(count($relatedLineItems), $result);
    }

    public function removeProductDataProvider(): array
    {
        $lineItem1 = $this->getLineItem(35);
        $lineItem2 = $this->getLineItem(36);
        $lineItem3 = $this->getLineItem(37);

        return [
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem1, $lineItem3],
                'flush' => true,
                'expectedFlush' => true,
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [],
                'flush' => true,
                'expectedFlush' => false,
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem2],
                'flush' => false,
                'expectedFlush' => false,
            ],
        ];
    }

    /**
     * @dataProvider getSimpleProductsProvider
     */
    public function testRemoveConfigurableProduct(array $simpleProducts, array $lineItems): void
    {
        $product = $this->getProduct(43, Product::TYPE_CONFIGURABLE);
        $shoppingList = $this->getShoppingList(1);
        foreach ($lineItems as $item) {
            $shoppingList->addLineItem($item);
        }

        $this->productVariantProvider->expects(self::once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($simpleProducts);

        $products = $simpleProducts;
        $products[] = $product;

        $this->lineItemRepository->expects(self::once())
            ->method('getItemsByShoppingListAndProducts')
            ->with($shoppingList, $products)
            ->willReturn($lineItems);

        $this->assertDeleteLineItems($lineItems);

        $result = $this->manager->removeProduct($shoppingList, $product, true);
        self::assertEquals(count($lineItems), $result);
    }

    public function getSimpleProductsProvider(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    $this->getProduct(44, Product::TYPE_SIMPLE),
                    $this->getProduct(45, Product::TYPE_SIMPLE),
                    $this->getProduct(46, Product::TYPE_SIMPLE),
                ],
                [
                    $this->getLineItem(38),
                    $this->getLineItem(39),
                    $this->getLineItem(40),
                ],
            ],
        ];
    }

    public function testBulkAddLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $lineItems = [];
        for ($i = 0; $i < 10; $i++) {
            $lineItems[] = new LineItem();
        }

        $this->em
            ->expects(self::exactly(2))
            ->method('flush');

        $this->totalManager
            ->expects(self::once())
            ->method('recalculateTotals')
            ->with($shoppingList, false);

        $this->manager->bulkAddLineItems($lineItems, $shoppingList, 5);
        self::assertCount(10, $shoppingList->getLineItems());
    }

    public function testBulkAddLineItemsWithEmptyLineItems(): void
    {
        self::assertEquals(0, $this->manager->bulkAddLineItems([], new ShoppingList(), 10));
    }

    public function testBulkAddLineItemsWithChecksum(): void
    {
        $shoppingList = new ShoppingList();
        $lineItems = [];
        for ($i = 1; $i <= 3; $i++) {
            $lineItems[] = (new LineItemStub())->setId($i);
        }

        $this->lineItemChecksumGenerator
            ->expects(self::exactly(3))
            ->method('getChecksum')
            ->willReturnCallback(static fn (LineItem $lineItem) => 'checksum_' . $lineItem->getId());

        $this->em
            ->expects(self::once())
            ->method('flush');

        $this->totalManager
            ->expects(self::once())
            ->method('recalculateTotals')
            ->with($shoppingList, false);

        $this->manager->bulkAddLineItems($lineItems, $shoppingList, 5);
        self::assertCount(3, $shoppingList->getLineItems());
        self::assertEquals('checksum_1', $shoppingList->getLineItems()[0]->getChecksum());
        self::assertEquals('checksum_2', $shoppingList->getLineItems()[1]->getChecksum());
        self::assertEquals('checksum_3', $shoppingList->getLineItems()[2]->getChecksum());
    }

    public function testEdit(): void
    {
        $label = 'test label';

        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $shoppingList = new ShoppingList();

        self::assertSame($shoppingList, $this->manager->edit($shoppingList, $label));
        self::assertEquals($label, $shoppingList->getLabel());
        self::assertSame($customerUser, $shoppingList->getCustomerUser());
        self::assertSame($customerUser->getCustomer(), $shoppingList->getCustomer());
        self::assertSame($customerUser->getOrganization(), $shoppingList->getOrganization());
        self::assertSame($website, $shoppingList->getWebsite());
    }

    public function testRemoveLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem1 = new LineItem();
        $shoppingList->addLineItem($lineItem1);
        $lineItem2 = new LineItem();
        $shoppingList->addLineItem($lineItem2);

        $this->assertDeleteLineItems([$lineItem1, $lineItem2]);

        $this->manager->removeLineItems($shoppingList);
    }

    public function testUpdateLineItem(): void
    {
        $lineItem = (new LineItem())
            ->setUnit($this->getProductUnit('test', 1))
            ->setQuantity(10);

        $shoppingList = $this->getShoppingList(1);
        $shoppingList->addLineItem($lineItem);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->updateLineItem($lineItemDuplicate, $shoppingList);

        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $resultingItem */
        $resultingItem = $shoppingList->getLineItems()->first();
        self::assertEquals(5, $resultingItem->getQuantity());
    }

    public function testUpdateLineItemWithChecksum(): void
    {
        $lineItem = (new LineItem())
            ->setChecksum('sample_checksum')
            ->setQuantity(10);
        $shoppingList = (new ShoppingList())
            ->addLineItem($lineItem);
        $checksum = 'new_checksum';

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        $this->manager->updateLineItem($lineItem, $shoppingList);
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals($checksum, $lineItem->getChecksum());
    }

    public function testUpdateAndRemoveLineItem(): void
    {
        $lineItem = (new LineItem())
            ->setUnit($this->getProductUnit('test', 1))
            ->setQuantity(10);

        $shoppingList = $this->getShoppingList(1);
        $shoppingList->addLineItem($lineItem);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(0);

        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);
        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with(LineItem::class)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with(self::identicalTo($lineItem));

        $this->manager->updateLineItem($lineItemDuplicate, $shoppingList);
    }

    public function testRemoveLineItemWithSimpleProductsInItems(): void
    {
        $product1 = new Product();
        $product2 = new Product();
        $lineItem1 = (new LineItem())
            ->setProduct($product1)
            ->setUnit($this->getProductUnit('test', 1))
            ->setQuantity(10);
        $lineItem2 = (new LineItem())
            ->setProduct($product2)
            ->setUnit($this->getProductUnit('test1', 1))
            ->setQuantity(2);

        $shoppingList = $this->getShoppingList(1);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);
        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with(LineItem::class)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with(self::identicalTo($lineItem1));

        $countDeletedItems = $this->manager->removeLineItem($lineItem1);

        self::assertEquals(1, $countDeletedItems);
    }

    public function testRemoveLineItemWithConfigurableProductsAndMatrixMatrixType(): void
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($this->getProductUnit('test', 1));

        $product = $this->getProduct(5, Product::TYPE_CONFIGURABLE);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $lineItem1 = new LineItem();
        $lineItem1->setProduct($this->getProduct(6, Product::TYPE_SIMPLE));
        $lineItem1->setParentProduct($product);
        $lineItem1->setUnit($this->getProductUnit('test', 1));

        $lineItem2 = new LineItem();
        $lineItem2->setProduct($this->getProduct(7, Product::TYPE_SIMPLE));
        $lineItem2->setParentProduct($product);
        $lineItem2->setUnit($this->getProductUnit('test', 1));

        $shoppingList = $this->getShoppingList(1);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        $lineItems = [$lineItem1, $lineItem2];
        $this->lineItemRepository->expects(self::once())
            ->method('getItemsByShoppingListAndProducts')
            ->with($shoppingList, [$product])
            ->willReturn($lineItems);

        $this->assertDeleteLineItems($lineItems);

        $countDeletedItems = $this->manager->removeLineItem($lineItem1);

        self::assertEquals(2, $countDeletedItems);
    }

    public function testRemoveLineItemWithConfigurableProductsAndWithFlagToDeleteOnlyCurrentItem(): void
    {
        $lineItem = new LineItem();
        $lineItem->setProduct($this->getProduct(11, Product::TYPE_SIMPLE));
        $lineItem->setParentProduct($this->getProduct(10, Product::TYPE_CONFIGURABLE));
        $lineItem->setUnit($this->getProductUnit('test', 1));

        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);
        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with(LineItem::class)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with(self::identicalTo($lineItem));

        $countDeletedItems = $this->manager->removeLineItem($lineItem, true);

        self::assertEquals(1, $countDeletedItems);
    }

    public function testActualizeLineItemsWhenNoDeletedLineItems(): void
    {
        $shoppingList = $this->getShoppingList(42);
        $allowedStatuses = ['in_stock'];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->lineItemRepository->expects(self::once())
            ->method('deleteNotAllowedLineItemsFromShoppingList')
            ->with($shoppingList, $allowedStatuses)
            ->willReturn(0);

        $this->totalManager->expects(self::never())
            ->method('recalculateTotals');

        $this->manager->actualizeLineItems($shoppingList);
    }

    public function testActualizeLineItemsWhenLineItemsDeleted(): void
    {
        $shoppingList = $this->getShoppingList(42);
        $allowedStatuses = ['in_stock'];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->lineItemRepository->expects(self::once())
            ->method('deleteNotAllowedLineItemsFromShoppingList')
            ->with($shoppingList, $allowedStatuses)
            ->willReturn(2);

        $this->totalManager->expects(self::once())
            ->method('recalculateTotals')
            ->with($shoppingList, true);

        $this->manager->actualizeLineItems($shoppingList);
    }
}
