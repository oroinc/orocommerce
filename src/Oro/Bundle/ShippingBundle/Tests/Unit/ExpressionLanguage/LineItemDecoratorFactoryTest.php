<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListenerExpressionLanguage;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecorator;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class LineItemDecoratorFactoryTest extends \PHPUnit_Framework_TestCase
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
     * @var LineItemDecoratorFactory
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

        $this->factory = new LineItemDecoratorFactory(
            $this->fieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper
        );
    }

    public function testCreateOrderLineItemDecorator()
    {
        $lineItem = new ShippingLineItem();
        $lineItem->setProduct($this->getEntity(Product::class, [
            'id' => 1
        ]));

        $decorator = $this->factory->createOrderLineItemDecorator([$lineItem], $lineItem);

        static::assertEquals(new LineItemDecorator($this->factory, [$lineItem], $lineItem), $decorator);
    }

    public function testCreateProductDecorator()
    {
        $product = $this->getEntity(Product::class, [
            'id' => 1
        ]);

        $lineItem = new ShippingLineItem();
        $lineItem->setProduct($product);

        $decorator = $this->factory->createProductDecorator([$lineItem], $product);

        static::assertEquals(new ProductDecorator(
            $this->fieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            [$product],
            $product
        ), $decorator);
    }
}
