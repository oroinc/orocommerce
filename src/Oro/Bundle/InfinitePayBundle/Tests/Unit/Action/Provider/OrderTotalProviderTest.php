<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Model\ResultElement;

/**
 * {@inheritdoc}
 */
class OrderTotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $subtotals = ['amount' => 10.00, 'currency' => 'EUR'];
    protected $shipping = ['amount' => 5.00];
    protected $discount = ['amount' => 2.5];
    protected $totalGrossAmount = 14.87;
    /**
     * @var InvoiceTotalsProviderInterface
     */
    protected $invoiceTotalsProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $invoiceTotalsProvider = $this
            ->getMockBuilder(InvoiceTotalsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $invoiceTotalsProvider->method('getDiscount')->willReturn($this->discount);
        $invoiceTotalsProvider->method('getTotalGrossAmount')->willReturn($this->totalGrossAmount);
        $taxTotals = new ResultElement();
        $taxTotals->offsetSet('excludingTax', 15.0);
        $invoiceTotalsProvider->method('getTaxTotals')->willReturn($taxTotals);
        $taxShipping = new ResultElement();
        $taxShipping->offsetSet('excludingTax', 8.43);
        $taxShipping->offsetSet('includingTax', 10);

        $invoiceTotalsProvider->method('getTaxShipping')->willReturn($taxShipping);

        $this->invoiceTotalsProvider = $invoiceTotalsProvider;
    }

    public function testGetOrderTotal()
    {
        $orderTotalProvider = new OrderTotalProvider($this->invoiceTotalsProvider);
        $order = (new Order())->setCurrency('EUR');
        $actualOrderTotals = $orderTotalProvider->getOrderTotal($order);

        $this->assertEquals('1487', $actualOrderTotals->getTrsAmtGross());
        $this->assertEquals('1500', $actualOrderTotals->getTrsAmtNet());
        $this->assertEquals($this->subtotals['currency'], $actualOrderTotals->getTrsCurrency());
        $this->assertEquals('1000', $actualOrderTotals->getShippingPriceGross());
        $this->assertEquals('843', $actualOrderTotals->getShippingPriceNet());
        $this->assertEquals('1', $actualOrderTotals->getPayType());
        $this->assertEquals('250', $actualOrderTotals->getRabateNet());
        $this->assertEquals('1', $actualOrderTotals->getTermsAccepted());
        $this->assertEquals(
            new \DateTime(),
            \DateTime::createFromFormat('Ymd His', $actualOrderTotals->getTrsDt()),
            null,
            2.0
        );
        $this->assertEquals('3', $actualOrderTotals->getTotalGrossCalcMethod());
    }
}
