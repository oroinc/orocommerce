<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class OrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderLineItemType();
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
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
                    'productUnitCode' => 'item',
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
                    ->setProductUnitCode('item')
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
