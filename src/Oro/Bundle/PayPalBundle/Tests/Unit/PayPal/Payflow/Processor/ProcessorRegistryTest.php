<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\FallbackProcessor;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry;

class ProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new ProcessorRegistry();
    }

    public function testAddProcessor()
    {
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())
            ->method('getCode')
            ->willReturn('PayPal');

        $this->registry->addProcessor($processor);

        $this->assertSame($processor, $this->registry->getProcessor('PayPal'));
    }

    public function testGetInvalidProcessor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Processor "not_supported" is missing. Registered processors are ""');

        $this->registry->getProcessor('not_supported');
    }

    public function testGetFallbackProcessor()
    {
        $processor = $this->registry->getProcessor(Partner::AMEX);
        $this->assertInstanceOf(FallbackProcessor::class, $processor);
    }
}
