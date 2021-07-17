<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;

class TaxAmountProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \stdClass
     */
    private $sourceEntity;

    /**
     * @var TaxProviderInterface|MockObject
     */
    private $taxProvider;

    /**
     * @var TaxAmountProvider
     */
    private $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->sourceEntity = new \stdClass();
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);

        $taxProviderRegistry
            ->expects($this->atLeastOnce())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxAmountProvider($taxProviderRegistry);
    }

    /**
     * @dataProvider getTaxAmountDataProvider
     *
     * @param float $taxAmount
     * @param float $expected
     */
    public function testGetTaxAmount($taxAmount, $expected)
    {
        $taxResultElement = new ResultElement([
            ResultElement::TAX_AMOUNT => $taxAmount
        ]);

        $taxResult = new Result([
            Result::TOTAL => $taxResultElement,
        ]);

        $this->taxProvider
            ->expects($this->once())
            ->method('loadTax')
            ->with($this->sourceEntity)
            ->willReturn($taxResult);

        $actual = $this->provider->getTaxAmount($this->sourceEntity);
        $this->assertSame($expected, $actual);
    }

    public function getTaxAmountDataProvider(): array
    {
        return [
            'amount should stay the same' => [10.0, 10.0],
            'zero should be zero' => [ 0, 0.0],
            'small amount in 1e-6 should be considered as 0' => [ 0.000001, 0.0],
        ];
    }

    /**
     * @param mixed $exceptionClass
     * @dataProvider getTaxAmountWithUnHandledExceptionDataProvider
     */
    public function testGetTaxAmountWithUnHandledException($exceptionClass): void
    {
        $this->taxProvider
            ->expects($this->once())
            ->method('loadTax')
            ->with($this->sourceEntity)
            ->willThrowException(new $exceptionClass);

        $this->expectException($exceptionClass);

        $this->provider->getTaxAmount($this->sourceEntity);
    }

    public function getTaxAmountWithUnHandledExceptionDataProvider(): array
    {
        return [
            [TaxationDisabledException::class],
            [UnmappableArgumentException::class],
            [\InvalidArgumentException::class],
            [\Exception::class],
        ];
    }
}
