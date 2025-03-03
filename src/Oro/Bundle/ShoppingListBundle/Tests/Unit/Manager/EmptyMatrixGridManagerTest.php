<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductProxyStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\LineItemByShoppingListAndProductFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Manager\EmptyMatrixGridManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmptyMatrixGridManagerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private LineItemByShoppingListAndProductFactoryInterface&MockObject $lineItemFactory;
    private ConfigManager&MockObject $configManager;
    private EmptyMatrixGridManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->lineItemFactory = $this->createMock(LineItemByShoppingListAndProductFactoryInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->manager = new EmptyMatrixGridManager(
            $this->doctrineHelper,
            $this->lineItemFactory,
            $this->configManager
        );
    }

    private function getProduct(int $id, string $type): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->setType($type);

        return $product;
    }

    private function getLineItem(int $id, ?Product $product = null, ?int $quantity = null): LineItem
    {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);
        if (null !== $product) {
            $lineItem->setProduct($product);
        }
        if (null !== $quantity) {
            $lineItem->setQuantity($quantity);
        }

        return $lineItem;
    }

    public function testAddEmptyMatrixShoppingListHasProductVariants(): void
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = $this->getProduct(100, Product::TYPE_CONFIGURABLE);
        $product->setPrimaryUnitPrecision($unitPrecision);
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(1, Product::TYPE_SIMPLE)));
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(2, Product::TYPE_SIMPLE)));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['shoppingList' => $shoppingList, 'unit' => $unit, 'parentProduct' => $product])
            ->willReturn(new LineItem());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    public function testAddEmptyMatrixShoppingListHasConfigurableProduct(): void
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = $this->getProduct(100, Product::TYPE_CONFIGURABLE);
        $product->setPrimaryUnitPrecision($unitPrecision);
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(1, Product::TYPE_SIMPLE)));
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(1, Product::TYPE_SIMPLE)));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['shoppingList' => $shoppingList, 'unit' => $unit, 'parentProduct' => $product]],
                [['shoppingList' => $shoppingList, 'unit' => $unit, 'product' => $product]]
            )
            ->willReturnOnConsecutiveCalls(null, new LineItem());

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    public function testAddEmptyMatrix(): void
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = $this->getProduct(100, Product::TYPE_CONFIGURABLE);
        $product->setPrimaryUnitPrecision($unitPrecision);
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(1, Product::TYPE_SIMPLE)));
        $product->addVariantLink(new ProductVariantLink($product, $this->getProduct(2, Product::TYPE_SIMPLE)));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['shoppingList' => $shoppingList, 'unit' => $unit, 'parentProduct' => $product]],
                [['shoppingList' => $shoppingList, 'unit' => $unit, 'product' => $product]]
            )
            ->willReturnOnConsecutiveCalls(null, null);

        $lineItem = new LineItem();

        $this->lineItemFactory->expects(self::once())
            ->method('create')
            ->with($shoppingList, $product)
            ->willReturn($lineItem);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with($lineItem);
        $entityManager->expects(self::once())
            ->method('flush');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(LineItem::class)
            ->willReturn($entityManager);

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    public function isAddEmptyMatrixAllowedDataProvider(): array
    {
        return [
            'empty line items, config disabled' => [
                'lineItems' => [
                    $this->getLineItem(1, null, 0),
                    $this->getLineItem(2, null, 0)
                ],
                'configAllowEmpty' => false,
                'expected' => false
            ],
            'empty line items, config enabled' => [
                'lineItems' => [
                    $this->getLineItem(1, null, 0),
                    $this->getLineItem(2, null, 0)
                ],
                'configAllowEmpty' => true,
                'expected' => true
            ],
            'not empty line items, config disabled' => [
                'lineItems' => [
                    $this->getLineItem(1, null, 0),
                    $this->getLineItem(2, null, 1)
                ],
                'configAllowEmpty' => false,
                'expected' => false
            ],
            'not empty line items, config enabled' => [
                'lineItems' => [
                    $this->getLineItem(1, null, 0),
                    $this->getLineItem(2, null, 1)
                ],
                'configAllowEmpty' => true,
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider isAddEmptyMatrixAllowedDataProvider
     */
    public function testIsAddEmptyMatrixAllowed(array $lineItems, bool $configAllowEmpty, bool $expected): void
    {
        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_product.matrix_form_allow_empty')
            ->willReturn($configAllowEmpty);

        self::assertEquals($expected, $this->manager->isAddEmptyMatrixAllowed($lineItems));
    }

    public function testHasEmptyMatrixTrue(): void
    {
        $shoppingList = new ShoppingList();
        $product = $this->getProduct(1, Product::TYPE_SIMPLE);
        $configurableProduct = $this->getProduct(100, Product::TYPE_CONFIGURABLE);

        $shoppingList->addLineItem($this->getLineItem(1, $product));
        $shoppingList->addLineItem($this->getLineItem(2, $product));
        $shoppingList->addLineItem($this->getLineItem(3, $configurableProduct));

        self::assertTrue($this->manager->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixFalse(): void
    {
        $shoppingList = new ShoppingList();
        $product = $this->getProduct(1, Product::TYPE_SIMPLE);

        $shoppingList->addLineItem($this->getLineItem(1, $product));
        $shoppingList->addLineItem($this->getLineItem(2, $product));
        $shoppingList->addLineItem($this->getLineItem(3, $product));

        self::assertFalse($this->manager->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixWhenCollectionEmpty(): void
    {
        $shoppingList = new ShoppingListStub();

        $lineItems = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );
        $lineItems->setInitialized(false);

        $shoppingList->setLineItems($lineItems);

        $entityRepository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepositoryForClass')
            ->with(ShoppingList::class)
            ->willReturn($entityRepository);

        self::assertFalse($this->manager->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixWhenCollectionUninitialized(): void
    {
        $shoppingList = new ShoppingListStub();

        $lineItems = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([$this->getLineItem(1, new ProductProxyStub())])
        );
        $lineItems->setInitialized(false);

        $shoppingList->setLineItems($lineItems);

        $entityRepository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(ShoppingList::class)
            ->willReturn($entityRepository);

        $entityRepository->expects(self::once())
            ->method('hasEmptyConfigurableLineItems')
            ->with($shoppingList)
            ->willReturn(true);

        self::assertTrue($this->manager->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixWhenProductsUninitialized(): void
    {
        $product = new ProductProxyStub();
        $shoppingList = new ShoppingListStub();

        $lineItems = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([
                $this->getLineItem(1, $product),
                $this->getLineItem(2, $product),
                $this->getLineItem(3, $product)
            ])
        );
        $lineItems->setInitialized(true);

        $shoppingList->setLineItems($lineItems);

        $entityRepository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(ShoppingList::class)
            ->willReturn($entityRepository);

        $entityRepository->expects(self::once())
            ->method('hasEmptyConfigurableLineItems')
            ->with($shoppingList)
            ->willReturn(false);

        self::assertFalse($this->manager->hasEmptyMatrix($shoppingList));
    }
}
