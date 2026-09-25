<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentRenderer;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTarget;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmbeddedFragmentRendererTest extends TestCase
{
    private const string BLOCK = 'embedded_profile';
    private const string ROUTE_NAME = 'oro_customer_frontend_customer_user_profile';

    private HttpKernelInterface&MockObject $httpKernel;

    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;

    private EmbeddedFragmentRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->renderer = new EmbeddedFragmentRenderer($this->httpKernel, $this->embeddedRequestChecker);
    }

    public function testRenderIncludesTheConfigScriptsAndTitleBlocksOnTheFirstGetRender(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/embedded/fragment/embedded_profile'), $this->createTarget());

        self::assertSame(
            [self::BLOCK, 'layout_js_modules_config', 'title'],
            $capturedRequest->query->all()['layout_block_ids']
        );
    }

    public function testRenderRequestsTheTargetAndTitleBlocksOnAGetFragmentReload(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/embedded/fragment/embedded_profile'), $this->createTarget());

        self::assertSame([self::BLOCK, 'title'], $capturedRequest->query->all()['layout_block_ids']);
    }

    public function testRenderRequestsOnlyTheTargetBlockOnAPostFragmentReload(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(
            Request::create('/embedded/fragment/embedded_profile', Request::METHOD_POST),
            $this->createTarget()
        );

        self::assertSame([self::BLOCK], $capturedRequest->query->all()['layout_block_ids']);
    }

    public function testRenderReturnsExactlyWhatTheKernelReturned(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);
        $response = new Response('rendered block');
        $this->httpKernel->method('handle')
            ->willReturn($response);

        $result = $this->renderer->render(Request::create('/'), $this->createTarget());

        self::assertSame($response, $result);
    }

    public function testRenderHandlesTheSubRequestAsASubRequestWithExceptionCatchingDisabled(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);

        $this->httpKernel->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(Request::class), HttpKernelInterface::SUB_REQUEST, false)
            ->willReturn(new Response());

        $this->renderer->render(Request::create('/'), $this->createTarget());
    }

    public function testRenderSetsRouteParamsFromTargetAttributesWithoutRouteOrController(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            self::BLOCK,
            self::ROUTE_NAME,
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => self::ROUTE_NAME, '_controller' => 'App\\Controller::action', 'id' => 42],
            []
        );

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/'), $target);

        self::assertSame(['id' => 42], $capturedRequest->attributes->get('_route_params'));
    }

    public function testRenderMarksTheSubRequestAsAnEmbeddedFragmentAndCarriesTheMatchedAttributes(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            self::BLOCK,
            self::ROUTE_NAME,
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => self::ROUTE_NAME, 'id' => 42],
            []
        );

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/'), $target);

        self::assertSame(self::ROUTE_NAME, $capturedRequest->attributes->get('_route'));
        self::assertSame(42, $capturedRequest->attributes->get('id'));
        self::assertTrue($capturedRequest->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE));
    }

    public function testRenderSetsRequestUriAndQueryStringServerParametersFromTheTargetUrl(): void
    {
        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            self::BLOCK,
            self::ROUTE_NAME,
            'https://storefront.example.test/customer/profile?tab=orders',
            '/customer/profile',
            [],
            []
        );

        $capturedRequest = null;
        $this->httpKernel->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/'), $target);

        self::assertSame(
            'https://storefront.example.test/customer/profile?tab=orders',
            $capturedRequest->server->get('REQUEST_URI')
        );
        self::assertSame('tab=orders', $capturedRequest->server->get('QUERY_STRING'));
    }

    private function createTarget(): EmbeddedFragmentTarget
    {
        return new EmbeddedFragmentTarget(
            self::BLOCK,
            self::ROUTE_NAME,
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => self::ROUTE_NAME],
            []
        );
    }
}
