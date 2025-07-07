<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\TotalsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TotalsProviderTest extends TestCase
{
    private TotalsProvider $provider;

    private MockObject&TotalProcessorProvider $totalProcessorProvider;

    protected function setUp(): void
    {
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->provider = new TotalsProvider($this->totalProcessorProvider);
    }

    public function testGetTotalWithSubtotalsAsArray(): void
    {
        $entity = new \stdClass();
        $expectedResult = [
            'total' => 100.0,
            'subtotals' => [
                ['label' => 'Subtotal', 'amount' => 100.0],
            ],
        ];

        $this->totalProcessorProvider
            ->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($entity)
            ->willReturn($expectedResult);

        self::assertSame($expectedResult, $this->provider->getTotalWithSubtotalsAsArray($entity));
    }

    public function testGetTotalWithSubtotalsAsArrayWithEmptyResult(): void
    {
        $entity = new \stdClass();
        $expectedResult = [];

        $this->totalProcessorProvider
            ->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($entity)
            ->willReturn($expectedResult);

        self::assertSame($expectedResult, $this->provider->getTotalWithSubtotalsAsArray($entity));
    }
}
