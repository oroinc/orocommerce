<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderLineItemType;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceMatchingProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendOrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceMatchingProvider
     */
    protected $productPriceMatchingProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->productPriceMatchingProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\ProductPriceMatchingProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FrontendOrderLineItemType($this->productPriceMatchingProvider);
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendOrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param array $submittedData
     * @param OrderLineItem $expectedData
     * @param Price $matchedPrice
     */
    public function testSubmit(array $options, array $submittedData, OrderLineItem $expectedData, Price $matchedPrice)
    {
        $this->productPriceMatchingProvider->expects($this->once())
            ->method('matchPrice')
            ->with(
                $this->isInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product'),
                $this->isInstanceOf('OroB2B\Bundle\ProductBundle\Entity\ProductUnit'),
                $this->isType('int'),
                $this->isType('string')
            )
            ->willReturn($matchedPrice);

        parent::testSubmit($options, $submittedData, $expectedData);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id');
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));
        $price = 42.42;
        $currency = 'USD';

        return [
            'default' => [
                'options' => [
                    'currency' => $currency,
                ],
                'submittedData' => [
                    'product' => 1,
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPrice(Price::create($price, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'matchedPrice' => Price::create($price, $currency)
            ]
        ];
    }
}
