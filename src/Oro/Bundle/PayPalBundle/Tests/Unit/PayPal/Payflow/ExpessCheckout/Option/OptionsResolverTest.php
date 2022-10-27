<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\AccessException;

class OptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var OptionsResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OptionsResolver();
    }

    public function testAddOption()
    {
        $option = $this->createMock(OptionInterface::class);
        $option->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);

        $this->resolver->addOption($option);
    }

    public function testAddNotApplicableDependentOption()
    {
        $option = $this->createMock(OptionsDependentInterface::class);
        $option->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);
        $option->expects($this->once())
            ->method('isApplicableDependent')
            ->willReturn(false);
        $option->expects($this->never())
            ->method('configureDependentOption');

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }

    public function testAddApplicableDependentOption()
    {
        $option = $this->createMock(OptionsDependentInterface::class);
        $option->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);
        $option->expects($this->once())
            ->method('isApplicableDependent')
            ->with([])
            ->willReturn(true);
        $option->expects($this->once())
            ->method('configureDependentOption')
            ->with($this->resolver, []);

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }

    public function testAddOptionInResolveAction()
    {
        $this->expectException(AccessException::class);
        $this->expectExceptionMessage('addOption is locked during resolve process');

        $option = $this->createMock(OptionsDependentInterface::class);
        $option->expects($this->once())
            ->method('configureOption')
            ->with($this->resolver);
        $option->expects($this->once())
            ->method('isApplicableDependent')
            ->with([])
            ->willReturn(true);
        $option->expects($this->once())
            ->method('configureDependentOption')
            ->with($this->resolver, [])
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->addOption($this->createMock(OptionsDependentInterface::class));
            });

        $this->resolver->addOption($option);

        $this->resolver->resolve();
    }
}
