<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry;

class ProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProcessorRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new ProcessorRegistry();
    }

    public function testAddProcessor()
    {
        $processor = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorInterface');
        $processor->expects($this->once())->method('getCode')->willReturn('PayPal');

        $this->registry->addProcessor($processor);

        $this->assertSame($processor, $this->registry->getProcessor('PayPal'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Processor "not_supported" is missing. Registered processors are ""
     */
    public function testGetInvalidProcessor()
    {
        $this->registry->getProcessor('not_supported');
    }

    public function testGetFallbackProcessor()
    {
        $processor = $this->registry->getProcessor(Partner::AMEX);
        $this->assertInstanceOf('Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\FallbackProcessor', $processor);
    }
}
