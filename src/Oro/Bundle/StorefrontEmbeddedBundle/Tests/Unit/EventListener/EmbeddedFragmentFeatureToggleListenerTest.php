<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\EmbeddedFragmentFeatureToggleListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmbeddedFragmentFeatureToggleListenerTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;

    private EmbeddedFragmentFeatureToggleListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new EmbeddedFragmentFeatureToggleListener($this->featureChecker);
    }

    public function testDeniesTheFragmentSubRequestWhenTheTargetRouteIsDisabled(): void
    {
        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_route' => 'oro_customer_frontend_customer_user_profile',
            EmbeddedFragmentController::BLOCK_ATTRIBUTE => 'embedded_profile',
        ]);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_customer_frontend_customer_user_profile', 'routes')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The page behind embedded block "embedded_profile" is disabled.');

        $this->listener->onKernelRequest($event);
    }

    public function testPassesTheFragmentSubRequestWhenTheTargetRouteIsEnabled(): void
    {
        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_route' => 'oro_customer_frontend_customer_user_profile',
            EmbeddedFragmentController::BLOCK_ATTRIBUTE => 'embedded_profile',
        ]);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_customer_frontend_customer_user_profile', 'routes')
            ->willReturn(true);

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @dataProvider skipsARequestThatNeedsNoFeatureToggleCheckProvider
     *
     * @param array<string, mixed> $attributes
     */
    public function testSkipsARequestThatNeedsNoFeatureToggleCheck(array $attributes, int $requestType): void
    {
        $this->featureChecker
            ->expects(self::never())
            ->method('isResourceEnabled');

        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes->add($attributes);
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
        $this->listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function skipsARequestThatNeedsNoFeatureToggleCheckProvider(): iterable
    {
        yield 'main request' => [
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            HttpKernelInterface::MAIN_REQUEST,
        ];
        yield 'fragment sub-request without a route' => [
            [
                EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
                EmbeddedFragmentController::BLOCK_ATTRIBUTE => 'embedded_profile',
            ],
            HttpKernelInterface::SUB_REQUEST,
        ];
        yield 'fragment sub-request with a route that is not a string' => [
            [
                EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
                '_route' => ['not', 'a', 'string'],
                EmbeddedFragmentController::BLOCK_ATTRIBUTE => 'embedded_profile',
            ],
            HttpKernelInterface::SUB_REQUEST,
        ];
    }
}
