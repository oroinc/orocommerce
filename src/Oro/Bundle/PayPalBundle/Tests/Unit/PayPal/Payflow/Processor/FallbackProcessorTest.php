<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\FallbackProcessor;

class FallbackProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FallbackProcessor
     */
    protected $processor;

    protected function setUp(): void
    {
        $this->processor = new FallbackProcessor();
    }

    public function testConfigureOptionsDoNothing()
    {
        $resolver = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver');
        $resolver->expects($this->never())->method($this->anything());

        $this->processor->configureOptions($resolver);
    }

    public function testGetCodeThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->processor->getCode();
    }

    public function testGetNameThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->processor->getName();
    }
}
