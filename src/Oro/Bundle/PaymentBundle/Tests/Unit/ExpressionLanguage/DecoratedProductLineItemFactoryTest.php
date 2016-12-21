<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListenerExpressionLanguage;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator;
use Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter;
use Oro\Bundle\ProductBundle\Entity\Product;
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
        /** @var Product $product */
        $product = $this->getEntity(
            Product::class,
            [
                'id' => 1,
            ]
        );
        $copiedLineItem = new PaymentLineItem(
            [
                PaymentLineItem::FIELD_PRODUCT => $product,
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
                function (PaymentLineItemInterface $lineItem) {
                    return $lineItem->getProduct();
                },
                $lineItems
            ),
            $product
        );

        $expectedLineItem = new PaymentLineItem(
            [
                PaymentLineItem::FIELD_PRICE => $copiedLineItem->getPrice(),
                PaymentLineItem::FIELD_PRODUCT_UNIT => $copiedLineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $copiedLineItem->getProductUnitCode(),
                PaymentLineItem::FIELD_QUANTITY => $copiedLineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $copiedLineItem->getProductHolder(),
                PaymentLineItem::FIELD_PRODUCT_SKU => $copiedLineItem->getProductSku(),
                PaymentLineItem::FIELD_PRODUCT => $expectedDecoratedProduct,
            ]
        );

        static::assertEquals($expectedLineItem, $actualLineItem);
    }
}
