<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\Action\AddConfigurableProductToShoppingListAction;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\LineItemByShoppingListAndProductFactoryInterface;
use PHPUnit\Framework\TestCase;

class AddConfigurableProductToShoppingListActionTest extends TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var LineItemByShoppingListAndProductFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemFactory;

    /**
     * @var AddConfigurableProductToShoppingListAction
     */
    private $action;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->lineItemFactory = $this->createMock(LineItemByShoppingListAndProductFactoryInterface::class);

        $this->action = new AddConfigurableProductToShoppingListAction(
            $this->doctrineHelper,
            $this->lineItemFactory
        );
    }

    public function testShoppingListHasProductVariants()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = new Product();

        $variantProducts = [
            new Product(),
            new Product(),
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
                'product' => $variantProducts,
            ])
            ->willReturn(new LineItem());

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $this->action->execute($shoppingList, $product);
    }

    public function testShoppingListHasConfigurableProduct()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $product = new Product();

        $variantProducts = [
            new Product(),
            new Product(),
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
                    'product' => $variantProducts,
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

        $this->action->execute($shoppingList, $product);
    }

    public function testAddConfigurableProductToShoppingList()
    {
        $shoppingList = new ShoppingList();
        $unit = new ProductUnit();

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        $variantProducts = [
            new Product(),
            new Product(),
        ];

        $product = new Product();
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
                    'product' => $variantProducts,
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

        $this->action->execute($shoppingList, $product);
    }
}
