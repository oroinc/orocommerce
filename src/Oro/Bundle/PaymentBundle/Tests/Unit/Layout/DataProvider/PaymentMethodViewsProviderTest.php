<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodViewsProvider;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PaymentMethodViewsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const METHOD = 'Method';

    /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodViewProvider;

    /** @var ApplicablePaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var PaymentMethodViewsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentMethodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);
        $this->paymentMethodProvider = $this->createMock(ApplicablePaymentMethodsProvider::class);
        $this->cacheProvider = $this->createMock(CacheInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->provider = new PaymentMethodViewsProvider(
            $this->paymentMethodViewProvider,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider,
            $this->cacheProvider
        );
    }

    public function testGetViewsEmpty()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([]);

        $this->paymentMethodViewProvider->expects(self::never())
            ->method('getPaymentMethodViews');

        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            PaymentMethodViewsProvider::class . md5(serialize($context))
        );

        $this->cacheProvider->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $data = $this->provider->getViews($context);
        $this->assertEmpty($data);
    }

    public function testGetViews()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $methodType = 'payment_method';
        $paymentMethodViews = [$methodType => ['label' => 'label', 'block' => 'block', 'options' => []]];

        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            PaymentMethodViewsProvider::class . md5(serialize($context))
        );

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $this->cacheProvider->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $view->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');
        $view->expects($this->once())
            ->method('getBlock')
            ->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($context)
            ->willReturn([]);
        $view->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodViews')
            ->with([$methodType])
            ->willReturn([$view]);

        $data = $this->provider->getViews($context);
        $this->assertSame($paymentMethodViews, $data);
    }

    public function testGetViewsCached()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $methodType = 'payment_method';
        $paymentMethodViews = [$methodType => ['label' => 'label', 'block' => 'block', 'options' => []]];

        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            PaymentMethodViewsProvider::class . md5(serialize($context))
        );

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $saveCallback = function ($cacheKey, $callback) {
            $item = $this->createMock(ItemInterface::class);
            return $callback($item);
        };
        $this->cacheProvider->expects($this->exactly(2))
            ->method('get')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(new ReturnCallback($saveCallback), $paymentMethodViews);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $view->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');
        $view->expects($this->once())
            ->method('getBlock')
            ->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($context)
            ->willReturn([]);
        $view->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodViews')
            ->with([$methodType])
            ->willReturn([$view]);

        $data = $this->provider->getViews($context);
        $this->assertSame($paymentMethodViews, $data);

        $data = $this->provider->getViews($context);
        $this->assertSame($paymentMethodViews, $data);
    }

    public function testGetPaymentMethods()
    {
        $entity = new \stdClass();
        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($entity);
        $this->provider->getPaymentMethods($entity);
    }
}
