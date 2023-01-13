<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\PayPal;

class PayPalTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayPal */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new PayPal();
    }

    public function testConfigureOptionsDoNothingIfNoSwipe()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('isDefined')
            ->with('SWIPE')
            ->willReturn(false);
        $resolver->expects($this->never())
            ->method('remove');

        $this->processor->configureOptions($resolver);
    }

    public function testConfigureOptionsRemoveSwipe()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('isDefined')
            ->with('SWIPE')
            ->willReturn(true);
        $resolver->expects($this->once())
            ->method('remove')
            ->with('SWIPE');

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
