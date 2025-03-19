<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Stub\PaymentMethodGroupAwareViewStub;
use PHPUnit\Framework\TestCase;

final class CompositePaymentMethodViewProviderTest extends TestCase
{
    private CompositePaymentMethodViewProvider $compositeProvider;
    private iterable $innerProviders;

    protected function setUp(): void
    {
        $this->innerProviders = [
            $this->createMock(PaymentMethodViewProviderInterface::class),
            $this->createMock(PaymentMethodViewProviderInterface::class),
        ];
        $this->compositeProvider = new CompositePaymentMethodViewProvider($this->innerProviders);
    }

    public function testGetPaymentMethodViewsWithoutGroup(): void
    {
        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethodView1 = $this->createMock(PaymentMethodViewInterface::class);
        $paymentMethodView2 = $this->createMock(PaymentMethodViewInterface::class);

        $this->innerProviders[0]
            ->method('getPaymentMethodViews')
            ->with([$paymentMethodIdentifier1, $paymentMethodIdentifier2])
            ->willReturn([$paymentMethodView1]);

        $this->innerProviders[1]
            ->method('getPaymentMethodViews')
            ->with([$paymentMethodIdentifier1, $paymentMethodIdentifier2])
            ->willReturn([$paymentMethodView2]);

        $result = $this->compositeProvider->getPaymentMethodViews(
            [$paymentMethodIdentifier1, $paymentMethodIdentifier2]
        );

        self::assertCount(2, $result);
        self::assertContains($paymentMethodView1, $result);
        self::assertContains($paymentMethodView2, $result);
    }

    public function testGetPaymentMethodViewsWithGroup(): void
    {
        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethodGroup = 'group1';
        $paymentMethodView1 = new PaymentMethodGroupAwareViewStub(
            $paymentMethodIdentifier1,
            $paymentMethodGroup
        );
        $paymentMethodView2 = $this->createMock(PaymentMethodViewInterface::class);

        $this->innerProviders[0]
            ->method('getPaymentMethodViews')
            ->with([$paymentMethodIdentifier1, $paymentMethodIdentifier2])
            ->willReturn([$paymentMethodView1]);

        $this->innerProviders[1]
            ->method('getPaymentMethodViews')
            ->with([$paymentMethodIdentifier1, $paymentMethodIdentifier2])
            ->willReturn([$paymentMethodView2]);

        $this->compositeProvider->setPaymentMethodGroup($paymentMethodGroup);
        $result = $this->compositeProvider->getPaymentMethodViews(
            [$paymentMethodIdentifier1, $paymentMethodIdentifier2]
        );

        self::assertCount(1, $result);
        self::assertContains($paymentMethodView1, $result);
    }

    public function testGetPaymentMethodViewWithoutGroup(): void
    {
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);

        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $result = $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);

        self::assertSame($paymentMethodView, $result);
    }

    public function testGetPaymentMethodViewWithGroup(): void
    {
        $paymentMethodGroup = 'group1';
        $paymentMethodIdentifier = 'method1';
        $paymentMethodView = new PaymentMethodGroupAwareViewStub(
            $paymentMethodIdentifier,
            $paymentMethodGroup
        );

        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $this->compositeProvider->setPaymentMethodGroup($paymentMethodGroup);
        $result = $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);

        self::assertSame($paymentMethodView, $result);
    }

    public function testGetPaymentMethodViewThrowsExceptionWhenViewNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method view for "method1" identifier that is applicable for "" payment method group.'
        );

        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProviders[1]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);
    }

    public function testGetPaymentMethodViewThrowsExceptionWhenViewNotApplicableForGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethodView = new PaymentMethodGroupAwareViewStub($paymentMethodIdentifier, 'group2');

        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method view for "method1" identifier that is applicable '
            . 'for "group1" payment method group.'
        );

        $this->compositeProvider->setPaymentMethodGroup('group1');
        $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);
    }

    public function testHasPaymentMethodViewWithoutGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($this->createMock(PaymentMethodViewInterface::class));

        self::assertTrue($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodViewWithGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethodGroup = 'group1';
        $paymentMethodView = new PaymentMethodGroupAwareViewStub(
            $paymentMethodIdentifier,
            $paymentMethodGroup
        );

        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $this->compositeProvider->setPaymentMethodGroup($paymentMethodGroup);
        self::assertTrue($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodViewReturnsFalseWhenViewNotApplicableForGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethodView = new PaymentMethodGroupAwareViewStub($paymentMethodIdentifier, 'group2');

        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $this->compositeProvider->setPaymentMethodGroup('group1');
        self::assertFalse($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodViewReturnsFalseWhenViewNotFound(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProviders[1]
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        self::assertFalse($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }
}
