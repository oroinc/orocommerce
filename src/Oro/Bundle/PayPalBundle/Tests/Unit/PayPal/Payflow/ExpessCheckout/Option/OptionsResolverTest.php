<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class OptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var OptionsResolver */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resolver = new OptionsResolver();
    }

    protected function tearDown(): void
    {
        unset($this->resolver);
    }

    public function testAddOption()
    {
        /** @var OptionInterface|\PHPUnit\Framework\MockObject\MockObject $option */
        $option = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface');
        $option
            ->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);

        $this->resolver->addOption($option);
    }

    public function testAddNotApplicableDependentOption()
    {
        /** @var OptionInterface|\PHPUnit\Framework\MockObject\MockObject $option */
        $option = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface');
        $option
            ->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);

        $option
            ->expects($this->once())
            ->method('isApplicableDependent')
            ->willReturn(false);

        $option
            ->expects($this->never())
            ->method('configureDependentOption');

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }

    public function testAddApplicableDependentOption()
    {
        /** @var OptionInterface|\PHPUnit\Framework\MockObject\MockObject $option */
        $option = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface');
        $option
            ->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);

        $option
            ->expects($this->once())
            ->method('isApplicableDependent')
            ->with([])
            ->willReturn(true);

        $option
            ->expects($this->once())
            ->method('configureDependentOption')
            ->with($this->resolver, []);

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }

    public function testAddOptionInResolveAction()
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\AccessException::class);
        $this->expectExceptionMessage('addOption is locked during resolve process');

        /** @var OptionInterface|\PHPUnit\Framework\MockObject\MockObject $option */
        $option = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface');
        $option
            ->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);

        $option
            ->expects($this->once())
            ->method('isApplicableDependent')
            ->with([])
            ->willReturn(true);

        $option
            ->expects($this->once())
            ->method('configureDependentOption')
            ->with($this->resolver, [])
            ->willReturnCallback(function (OptionsResolver $resolver, array $options) {
                /** @var OptionInterface|\PHPUnit\Framework\MockObject\MockObject $option */
                $option = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface');
                $resolver->addOption($option);
            });

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }
}
