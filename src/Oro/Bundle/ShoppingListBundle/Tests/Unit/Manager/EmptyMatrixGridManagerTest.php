<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\LineItemByShoppingListAndProductFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Manager\EmptyMatrixGridManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class EmptyMatrixGridManagerTest extends TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var LineItemByShoppingListAndProductFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemFactory;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var EmptyMatrixGridManager
     */
    private $manager;

    protected function setUp()
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

    public function testAddEmptyMatrixShoppingListHasProductVariants()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = $this->getEntity(Product::class, ['id' => 100, 'type' => Product::TYPE_CONFIGURABLE]);

        $variantProducts = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]),
            $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]),
        ];

        $product
            ->setPrimaryUnitPrecision($unitPrecision)
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[0]))
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[1]));

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('findOneBy')
            ->with([
                'shoppingList' => $shoppingList,
                'unit' => $unit,
                'parentProduct' => $product,
            ])
            ->willReturn(new LineItem());

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityManagerForClass');

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    public function testAddEmptyMatrixShoppingListHasConfigurableProduct()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = $this->getEntity(Product::class, ['id' => 100, 'type' => Product::TYPE_CONFIGURABLE]);

        $variantProducts = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]),
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]),
        ];

        $product
            ->setPrimaryUnitPrecision($unitPrecision)
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[0]))
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[1]));

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [[
                    'shoppingList' => $shoppingList,
                    'unit' => $unit,
                    'parentProduct' => $product,
                ]],
                [[
                    'shoppingList' => $shoppingList,
                    'unit' => $unit,
                    'product' => $product,
                ]]
            )
            ->willReturnOnConsecutiveCalls(null, new LineItem());

        $this->doctrineHelper
            ->expects(static::exactly(2))
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityManagerForClass');

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    public function testAddEmptyMatrix()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $variantProducts = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]),
            $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]),
        ];

        $product = $this->getEntity(Product::class, ['id' => 100, 'type' => Product::TYPE_CONFIGURABLE]);
        $product
            ->setPrimaryUnitPrecision($unitPrecision)
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[0]))
            ->addVariantLink(new ProductVariantLink($product, $variantProducts[1]));

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [[
                    'shoppingList' => $shoppingList,
                    'unit' => $unit,
                    'parentProduct' => $product,
                ]],
                [[
                    'shoppingList' => $shoppingList,
                    'unit' => $unit,
                    'product' => $product,
                ]]
            )
            ->willReturnOnConsecutiveCalls(null, null);

        $lineItem = new LineItem();

        $this->lineItemFactory
            ->expects(static::once())
            ->method('create')
            ->with($shoppingList, $product)
            ->willReturn($lineItem);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects(static::once())
            ->method('persist')
            ->with($lineItem);
        $entityManager
            ->expects(static::once())
            ->method('flush');

        $this->doctrineHelper
            ->expects(static::exactly(2))
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityManagerForClass')
            ->with(LineItem::class)
            ->willReturn($entityManager);

        $this->manager->addEmptyMatrix($shoppingList, $product);
    }

    /**
     * @return array
     */
    public function isAddEmptyMatrixAllowedDataProvider()
    {
        return [
            'empty line items, config disabled' => [
                'lineItems' => [
                    $this->getEntity(LineItem::class, ['id' => 1, 'quantity' => 0]),
                    $this->getEntity(LineItem::class, ['id' => 2, 'quantity' => 0]),
                ],
                'configAllowEmpty' => false,
                'expected' => false
            ],
            'empty line items, config enabled' => [
                'lineItems' => [
                    $this->getEntity(LineItem::class, ['id' => 1, 'quantity' => 0]),
                    $this->getEntity(LineItem::class, ['id' => 2, 'quantity' => 0]),
                ],
                'configAllowEmpty' => true,
                'expected' => true
            ],
            'not empty line items, config disabled' => [
                'lineItems' => [
                    $this->getEntity(LineItem::class, ['id' => 1, 'quantity' => 0]),
                    $this->getEntity(LineItem::class, ['id' => 2, 'quantity' => 1]),
                ],
                'configAllowEmpty' => false,
                'expected' => false
            ],
            'not empty line items, config enabled' => [
                'lineItems' => [
                    $this->getEntity(LineItem::class, ['id' => 1, 'quantity' => 0]),
                    $this->getEntity(LineItem::class, ['id' => 2, 'quantity' => 1]),
                ],
                'configAllowEmpty' => true,
                'expected' => false
            ],
        ];
    }

    /**
     * @param LineItem[] $lineItems
     * @param bool $configAllowEmpty
     * @param bool $expected
     * @dataProvider isAddEmptyMatrixAllowedDataProvider
     */
    public function testIsAddEmptyMatrixAllowed(array $lineItems, $configAllowEmpty, $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ALLOW_TO_ADD_EMPTY))
            ->willReturn($configAllowEmpty);

        $this->assertEquals($expected, $this->manager->isAddEmptyMatrixAllowed($lineItems));
    }

    public function testHasEmptyMatrixTrue()
    {
        $shoppingList = new ShoppingList();
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);
        $configurableProduct = $this->getEntity(Product::class, ['id' => 100, 'type' => Product::TYPE_CONFIGURABLE]);

        $shoppingList
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 1, 'product' => $product])
            )
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 2, 'product' => $product])
            )
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 3, 'product' => $configurableProduct])
            );

        self::assertTrue($this->manager->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixFalse()
    {
        $shoppingList = new ShoppingList();
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);

        $shoppingList
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 1, 'product' => $product])
            )
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 2, 'product' => $product])
            )
            ->addLineItem(
                $this->getEntity(LineItem::class, ['id' => 3, 'product' => $product])
            );

        self::assertFalse($this->manager->hasEmptyMatrix($shoppingList));
    }
}
