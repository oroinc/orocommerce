<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Manager;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;

class QuoteDemandManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $subtotalProvider;

    /** @var QuoteDemandManager */
    private $manager;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

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
