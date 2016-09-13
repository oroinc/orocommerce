<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\PayPal;

class PayPalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPal
     */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new PayPal();
    }

    public function testConfigureOptionsDoNothingIfNoSwipe()
    {
        $resolver = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver');
        $resolver->expects($this->once())->method('isDefined')->with('SWIPE')->willReturn(false);
        $resolver->expects($this->never())->method('remove');

        $this->processor->configureOptions($resolver);
    }


    public function testConfigureOptionsRemoveSwipe()
    {
        $resolver = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver');
        $resolver->expects($this->once())->method('isDefined')->with('SWIPE')->willReturn(true);
        $resolver->expects($this->once())->method('remove')->with('SWIPE');

        $this->processor->configureOptions($resolver);
    }

    public function testGetCode()
    {
        $this->assertEquals('PayPal', $this->processor->getCode());
    }

    public function testGetName()
    {
        $this->assertEquals('PayPal', $this->processor->getName());
    }
}
