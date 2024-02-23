<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Manager;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteDemandManagerTest extends TestCase
{
    private TotalProcessorProvider|MockObject $totalProvider;

    private LineItemSubtotalProvider|MockObject $subtotalProvider;

    private LineItemChecksumGeneratorInterface|MockObject $lineItemChecksumGenerator;

    private QuoteDemandManager $manager;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->manager = new QuoteDemandManager(
            $this->totalProvider,
            $this->subtotalProvider,
            $this->lineItemChecksumGenerator
        );
    }

    public function testRecalculateSubtotals(): void
    {
        $quoteDemand = new QuoteDemand();

        $subtotal = new Subtotal();
        $subtotal->setAmount(2.5)
            ->setCurrency('EUR');
        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->with($quoteDemand)
            ->willReturn($subtotal);

        $total = new Subtotal();
        $total->setAmount(123.1);
        $this->totalProvider->expects(self::once())
            ->method('getTotal')
            ->with($quoteDemand)
            ->willReturn($total);

        $this->manager->recalculateSubtotals($quoteDemand);
        self::assertEquals($subtotal->getAmount(), $quoteDemand->getSubtotal());
        self::assertEquals($total->getAmount(), $quoteDemand->getTotal());
        self::assertEquals('EUR', $quoteDemand->getTotalCurrency());
    }

    public function testUpdateQuoteProductDemandChecksum(): void
    {
        $quoteDemand = new QuoteDemand();

        $quoteDemand->addDemandProduct(new QuoteProductDemand($quoteDemand, new QuoteProductOffer(), 1));
        $quoteDemand->addDemandProduct(new QuoteProductDemand($quoteDemand, new QuoteProductOffer(), 2));
        $quoteDemand->addDemandProduct(new QuoteProductDemand($quoteDemand, new QuoteProductOffer(), 3));

        $this->lineItemChecksumGenerator->expects(self::exactly(3))
            ->method('getChecksum')
            ->willReturnOnConsecutiveCalls('checksum1', 'checksum2', null);

        $this->manager->updateQuoteProductDemandChecksum($quoteDemand);

        $demandProducts = $quoteDemand->getDemandProducts();
        self::assertEquals('checksum1', $demandProducts[0]->getChecksum());
        self::assertEquals('checksum2', $demandProducts[1]->getChecksum());
        self::assertEquals('', $demandProducts[2]->getChecksum());
    }
}
