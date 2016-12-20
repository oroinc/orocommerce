<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListenerExpressionLanguage;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DecoratedProductLineItemFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var SelectQueryConverter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var DecoratedProductLineItemFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->converter = $this->getMockBuilder(SelectQueryConverter::class)
            ->disableOriginalConstructor()->getMock();

        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->fieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->factory = new DecoratedProductLineItemFactory(
            $this->fieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper
        );
    }

    public function testCreateLineItemWithDecoratedProduct()
    {
        $product = $this->getEntity(
            Product::class,
            [
                'id' => 1,
            ]
        );
        $copiedLineItem = new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRODUCT => $product,
            ]
        );
        $lineItems = [$copiedLineItem];

        $actualLineItem = $this->factory->createLineItemWithDecoratedProductByLineItem(
            $lineItems,
            $copiedLineItem
        );

        $expectedDecoratedProduct = new ProductDecorator(
            $this->fieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            array_map(
                function (ShippingLineItemInterface $lineItem) {
                    return $lineItem->getProduct();
                },
                $lineItems
            ),
            $product
        );

        $expectedLineItem = new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRICE => $copiedLineItem->getPrice(),
                ShippingLineItem::FIELD_PRODUCT_UNIT => $copiedLineItem->getProductUnit(),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $copiedLineItem->getProductUnitCode(),
                ShippingLineItem::FIELD_QUANTITY => $copiedLineItem->getQuantity(),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $copiedLineItem->getProductHolder(),
                ShippingLineItem::FIELD_PRODUCT_SKU => $copiedLineItem->getProductSku(),
                ShippingLineItem::FIELD_WEIGHT => $copiedLineItem->getWeight(),
                ShippingLineItem::FIELD_DIMENSIONS => $copiedLineItem->getDimensions(),
                ShippingLineItem::FIELD_PRODUCT => $expectedDecoratedProduct,
            ]
        );

        static::assertEquals($expectedLineItem, $actualLineItem);
    }
}
