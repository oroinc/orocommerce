<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\PayPalCA;

class PayPalCATest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PayPalCA
     */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new PayPalCA();
    }

    public function testConfigureOptionsDoNothingIfNoSwipe()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())->method('isDefined')->with('SWIPE')->willReturn(false);
        $resolver->expects(self::never())->method('remove');

        $this->processor->configureOptions($resolver);
    }

    public function testConfigureOptionsRemoveSwipe()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())->method('isDefined')->with('SWIPE')->willReturn(true);
        $resolver->expects(self::once())->method('remove')->with('SWIPE');

        $this->processor->configureOptions($resolver);
    }

    public function testGetCode()
    {
        self::assertEquals('PayPalCA', $this->processor->getCode());
    }

    public function testGetName()
    {
        self::assertEquals('PayPalCA', $this->processor->getName());
    }
}
