<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\SummaryDataProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class SummaryDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    /**
     * @var SummaryDataProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutLineItemsManager = $this
            ->getMockBuilder('OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SummaryDataProvider(
            $this->checkoutLineItemsManager,
            $this->currencyProvider
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ArrayCollection $LineItems
     * @param Product $product1
     * @param Product $product2
     * @param Price $lineItem1Total
     * @param Price $lineItem2Total
     * @param Price $totalPrice
     */
    public function testGetData(
        ArrayCollection $LineItems,
        Product $product1,
        Product $product2,
        Price $lineItem1Total,
        Price $lineItem2Total,
        Price $totalPrice
    ) {
        $checkout = $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($LineItems);

        $this->currencyProvider->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $expected = [
            'lineItemTotals' => [
                $product1->getSku() => $lineItem1Total,
                $product2->getSku() => $lineItem2Total,
            ],
            'lineItems' => $LineItems,
            'lineItemsCount' => 110,
            'totalPrice' => $totalPrice
        ];

        $context = new LayoutContext();
        $context->data()->set('checkout', null, $checkout);

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        $product1 = (new Product())->setSku('productSku01');
        $product1Unit = new ProductUnit();
        $product1Unit->setCode('item');
        $product1quantity = 100;
        $product1price = new Price();
        $product1price->setValue(5);
        $product1price->setCurrency('USD');

        $product2 = (new Product())->setSku('productSku02');
        $product2Unit = new ProductUnit();
        $product2Unit->setCode('item');
        $product2quantity = 10;
        $product2price = new Price();
        $product2price->setValue(10);
        $product2price->setCurrency('USD');

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1)
            ->setProductSku($product1->getSku())
            ->setQuantity($product1quantity)
            ->setProductUnit($product1Unit)
            ->setProductUnitCode($product1Unit->getCode())
            ->setPrice($product1price);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2)
            ->setProductSku($product2->getSku())
            ->setQuantity($product2quantity)
            ->setProductUnit($product2Unit)
            ->setProductUnitCode($product2Unit->getCode())
            ->setPrice($product2price);

        $LineItems = new ArrayCollection();
        $LineItems->add($lineItem1);
        $LineItems->add($lineItem2);

        $lineItem1Total = new Price();
        $lineItem1Total->setValue($product1price->getValue() * $product1quantity);
        $lineItem1Total->setCurrency($product1price->getCurrency());

        $lineItem2Total = new Price();
        $lineItem2Total->setValue($product2price->getValue() * $product2quantity);
        $lineItem2Total->setCurrency($product1price->getCurrency());

        $totalPrice = new Price();
        $totalPriceValue = (float)$lineItem1Total->getValue() + (float)$lineItem2Total->getValue();
        $totalPrice->setValue($totalPriceValue);
        $totalPrice->setCurrency('USD');

        return [
            [
                'LineItems' => $LineItems,
                'product1' => $product1,
                'product2' => $product2,
                'lineItem1Total' => $lineItem1Total,
                'lineItem2Total' => $lineItem2Total,
                'totalPrice'=> $totalPrice
            ]
        ];
    }
}
