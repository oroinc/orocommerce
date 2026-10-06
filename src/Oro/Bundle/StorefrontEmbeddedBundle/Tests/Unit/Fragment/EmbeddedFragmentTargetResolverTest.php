<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTargetResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class EmbeddedFragmentTargetResolverTest extends TestCase
{
    private RouterInterface&MockObject $router;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
    }

    public function testResolveThrowsNotFoundWhenBlockIsUnknown(): void
    {
        $resolver = new EmbeddedFragmentTargetResolver($this->router, []);

        $this->router

            ->expects(self::never())
            ->method('generate');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown embedded block "embedded_missing".');

        $resolver->resolve('embedded_missing', Request::create('/'));
    }

    /**
     * @dataProvider resolveThrowsBadRequestWhenRouterCannotGenerateUrlForTheGivenParametersProvider
     */
    public function testResolveThrowsBadRequestWhenRouterCannotGenerateUrlForTheGivenParameters(
        \Throwable $generateException
    ): void {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willThrowException($generateException);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid parameters for embedded block "embedded_profile".');

        $resolver->resolve('embedded_profile', Request::create('/'));
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function resolveThrowsBadRequestWhenRouterCannotGenerateUrlForTheGivenParametersProvider(): iterable
    {
        yield 'missing mandatory parameters' => [
            new MissingMandatoryParametersException('oro_customer_frontend_customer_user_profile', ['id'])
        ];
        yield 'invalid parameter' => [new InvalidParameterException()];
    }

    public function testResolveThrowsNotFoundWhenTheConfiguredRouteDoesNotExist(): void
    {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willThrowException(new RouteNotFoundException());

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Embedded block "embedded_profile" points at an unknown route.');

        $resolver->resolve('embedded_profile', Request::create('/'));
    }

    /**
     * @dataProvider resolveThrowsNotFoundWhenTheRouterCannotMatchTheGeneratedPathProvider
     */
    public function testResolveThrowsNotFoundWhenTheRouterCannotMatchTheGeneratedPath(
        \Throwable $matchException
    ): void {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn(new RequestContext());
        $this->router
            ->method('generate')
            ->willReturn('/customer/profile');
        $this->router
            ->expects(self::once())
            ->method('match')
            ->willThrowException($matchException);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Cannot resolve the target route of embedded block "embedded_profile".');

        $resolver->resolve('embedded_profile', Request::create('/'));
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function resolveThrowsNotFoundWhenTheRouterCannotMatchTheGeneratedPathProvider(): iterable
    {
        yield 'resource not found' => [new ResourceNotFoundException()];
        yield 'method not allowed' => [new MethodNotAllowedException(['POST'])];
    }

    public function testResolveThrowsNotFoundWhenTheMatchedRouteDiffersFromTheConfiguredOne(): void
    {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn(new RequestContext());
        $this->router
            ->method('generate')
            ->willReturn('/customer/profile');
        $this->router
            ->method('match')
            ->willReturn(['_route' => 'another_route_serving_the_same_path']);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The url of embedded block "embedded_profile" is served by another route.');

        $resolver->resolve('embedded_profile', Request::create('/'));
    }

    public function testResolveFiltersInternalParametersBeforeGeneratingTheUrl(): void
    {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->query->replace(['tab' => 'orders', '_csrf_token' => 'abc', '_widgetContainer' => 'dialog']);

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn(new RequestContext());
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('oro_customer_frontend_customer_user_profile', ['tab' => 'orders'])
            ->willReturn('/customer/profile?tab=orders');
        $this->router
            ->method('match')
            ->willReturn(['_route' => 'oro_customer_frontend_customer_user_profile']);

        $target = $resolver->resolve('embedded_profile', $request);

        self::assertSame(['tab' => 'orders'], $target->routeParameters);
    }

    public function testResolveReturnsTheTargetResolvedFromTheRouter(): void
    {
        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $request = Request::create('/embedded/fragment/embedded_profile');
        $request->query->replace(['tab' => 'orders']);

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn(new RequestContext());
        $this->router
            ->method('generate')
            ->with('oro_customer_frontend_customer_user_profile', ['tab' => 'orders'])
            ->willReturn('https://storefront.example.test/customer/profile?tab=orders');
        $this->router
            ->method('match')
            ->with('/customer/profile')
            ->willReturn(['_route' => 'oro_customer_frontend_customer_user_profile', 'tab' => 'orders']);

        $target = $resolver->resolve('embedded_profile', $request);

        self::assertSame('embedded_profile', $target->block);
        self::assertSame('oro_customer_frontend_customer_user_profile', $target->routeName);
        self::assertSame('https://storefront.example.test/customer/profile?tab=orders', $target->url);
        self::assertSame('/customer/profile', $target->path);
        self::assertSame(
            ['_route' => 'oro_customer_frontend_customer_user_profile', 'tab' => 'orders'],
            $target->attributes
        );
        self::assertSame(['tab' => 'orders'], $target->routeParameters);
    }

    public function testResolveStripsTheRouterBaseUrlFromThePath(): void
    {
        $context = new RequestContext();
        $context->setBaseUrl('/app_dev.php');
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $resolver = new EmbeddedFragmentTargetResolver(
            $this->router,
            ['embedded_profile' => 'oro_customer_frontend_customer_user_profile']
        );

        $this->router

            ->method('generate')
            ->willReturn('https://storefront.example.test/app_dev.php/customer/profile');
        $this->router
            ->method('match')
            ->with('/customer/profile')
            ->willReturn(['_route' => 'oro_customer_frontend_customer_user_profile']);

        $target = $resolver->resolve('embedded_profile', Request::create('/'));

        self::assertSame('/customer/profile', $target->path);
    }
}
