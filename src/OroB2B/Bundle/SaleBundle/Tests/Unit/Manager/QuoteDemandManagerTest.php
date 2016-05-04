<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Manager;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Manager\QuoteDemandManager;

class QuoteDemandManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProvider;

    /**
     * @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subtotalProvider;

    /**
     * @var QuoteDemandManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->totalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->subtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new QuoteDemandManager(
            $this->totalProvider,
            $this->subtotalProvider
        );
    }

    public function testRecalculateSubtotals()
    {
        $quoteDemand = new QuoteDemand();

        $subtotal = new Subtotal();
        $subtotal->setAmount(2.5)
            ->setCurrency('EUR');
        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotal')
            ->with($quoteDemand)
            ->willReturn($subtotal);

        $total = new Subtotal();
        $total->setAmount(123.1);
        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($quoteDemand)
            ->willReturn($total);

        $this->manager->recalculateSubtotals($quoteDemand);
        $this->assertEquals($subtotal->getAmount(), $quoteDemand->getSubtotal());
        $this->assertEquals($total->getAmount(), $quoteDemand->getTotal());
        $this->assertEquals('EUR', $quoteDemand->getTotalCurrency());
    }
}
