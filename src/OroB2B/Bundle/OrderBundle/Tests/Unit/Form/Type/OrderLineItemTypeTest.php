<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class OrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    const PRODUCT_UNIT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit';
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id'),
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2, 'id'),
            ],
            ProductSelectType::NAME
        );

        return array_merge(
            parent::getExtensions(),
            [new PreloadedExtension([$productSelectType->getName() => $productSelectType], [])]
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue([
                'item' => 'item',
                'kg' => 'kilogram',
            ]));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::PRODUCT_UNIT_CLASS)
            ->will($this->returnValue($repository));

        $this->formType = new OrderLineItemType($this->registry, $this->productUnitLabelFormatter);
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
        $this->formType->setProductUnitClass(self::PRODUCT_UNIT_CLASS);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id');
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));

        return [
            'default' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'product' => 1,
                    'productSku' => '',
                    'freeFormProduct' => '',
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'productUnitCode' => '',
                    'price' => [
                        'value' => '5',
                        'currency' => 'USD',
                    ],
                    'priceType' => OrderLineItem::PRICE_TYPE_BUNDLED,
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_BUNDLED)
                    ->setShipBy($date)
                    ->setComment('Comment')
            ],
            'free form entry' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'product' => null,
                    'productSku' => 'SKU02',
                    'freeFormProduct' => 'Service',
                    'quantity' => 1,
                    'productUnit' => 'item',
                    'price' => [
                        'value' => 5,
                        'currency' => 'USD',
                    ],
                    'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setQuantity(1)
                    ->setFreeFormProduct('Service')
                    ->setProductSku('SKU02')
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment')
            ],
        ];
    }

    public function testBuildView()
    {
        $this->assertDefaultBuildViewCalled();
    }
}
