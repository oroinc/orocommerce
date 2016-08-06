<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\FallbackProcessor;

class FallbackProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FallbackProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new FallbackProcessor();
    }

    public function testConfigureOptionsDoNothing()
    {
        $resolver = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver');
        $resolver->expects($this->never())->method($this->anything());

        $this->processor->configureOptions($resolver);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetCodeThrowsException()
    {
        $this->processor->getCode();
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetNameThrowsException()
    {
        $this->processor->getName();
    }
}
