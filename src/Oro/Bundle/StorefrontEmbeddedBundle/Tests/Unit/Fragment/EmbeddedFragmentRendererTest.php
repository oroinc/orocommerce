<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
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
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        $this->renderer->render(Request::create('/embedded/fragment/embedded_profile'), $target);

        self::assertSame(
            ['embedded_profile', 'layout_js_modules_config', 'title'],
            $capturedRequest->query->all()['layout_block_ids']
        );
    }

    public function testRenderRequestsTheTargetAndTitleBlocksOnAGetHashNavigationReload(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        $this->renderer->render(Request::create('/embedded/fragment/embedded_profile'), $target);

        self::assertSame(['embedded_profile', 'title'], $capturedRequest->query->all()['layout_block_ids']);
    }

    public function testRenderRequestsOnlyTheTargetBlockOnAPostHashNavigationReload(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        $this->renderer->render(
            Request::create('/embedded/fragment/embedded_profile', Request::METHOD_POST),
            $target
        );

        self::assertSame(['embedded_profile'], $capturedRequest->query->all()['layout_block_ids']);
    }

    public function testRenderReturnsExactlyWhatTheKernelReturned(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);
        $response = new Response('rendered block');
        $this->httpKernel
            ->method('handle')
            ->willReturn($response);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        $result = $this->renderer->render(Request::create('/'), $target);

        self::assertSame($response, $result);
    }

    public function testRenderHandlesTheSubRequestAsASubRequestWithExceptionCatchingDisabled(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $response = new Response();
        $this->httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(Request::class), HttpKernelInterface::SUB_REQUEST, false)
            ->willReturn($response);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        self::assertSame($response, $this->renderer->render(Request::create('/'), $target));
    }

    public function testRenderSetsRouteParamsFromTargetAttributesWithoutRouteOrController(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [
                '_route' => 'oro_customer_frontend_customer_user_profile',
                '_controller' => 'App\\Controller::action',
                'id' => 42
            ],
            []
        );

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/'), $target);

        self::assertSame(['id' => 42], $capturedRequest->attributes->get('_route_params'));
    }

    public function testRenderMarksTheSubRequestAsAnEmbeddedFragmentAndCarriesTheMatchedAttributes(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile', 'id' => 42],
            []
        );

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/'), $target);

        self::assertSame('oro_customer_frontend_customer_user_profile', $capturedRequest->attributes->get('_route'));
        self::assertSame(42, $capturedRequest->attributes->get('id'));
        self::assertTrue($capturedRequest->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE));
    }

    /**
     * @dataProvider renderTakesTheControllerOnlyFromTheTargetAttributesProvider
     *
     * @param array<string, string> $targetAttributes
     */
    public function testRenderTakesTheControllerOnlyFromTheTargetAttributes(
        array $targetAttributes,
        bool $expectedHasController,
        ?string $expectedController
    ): void {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->attributes->add([
            '_route' => EmbeddedFragmentController::ROUTE_NAME,
            '_controller' => EmbeddedFragmentController::class . '::fragmentAction',
            EmbeddedFragmentController::BLOCK_ATTRIBUTE => 'embedded_profile',
            '_locale' => 'en',
        ]);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            $targetAttributes,
            []
        );

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render($mainRequest, $target);

        self::assertSame($expectedHasController, $capturedRequest->attributes->has('_controller'));
        self::assertSame($expectedController, $capturedRequest->attributes->get('_controller'));
        self::assertSame(
            'embedded_profile',
            $capturedRequest->attributes->get(EmbeddedFragmentController::BLOCK_ATTRIBUTE)
        );
        self::assertSame('en', $capturedRequest->attributes->get('_locale'));
        self::assertSame(
            EmbeddedFragmentController::class . '::fragmentAction',
            $mainRequest->attributes->get('_controller')
        );
    }

    /**
     * @return iterable<string, array{
     *     targetAttributes: array<string, string>,
     *     expectedHasController: bool,
     *     expectedController: string|null
     * }>
     */
    public static function renderTakesTheControllerOnlyFromTheTargetAttributesProvider(): iterable
    {
        yield 'target without a controller' => [
            'targetAttributes' => ['_route' => 'oro_customer_frontend_customer_user_profile'],
            'expectedHasController' => false,
            'expectedController' => null,
        ];
        yield 'target with a controller' => [
            'targetAttributes' => [
                '_route' => 'oro_customer_frontend_customer_user_profile',
                '_controller' => 'App\\Controller::action'
            ],
            'expectedHasController' => true,
            'expectedController' => 'App\\Controller::action',
        ];
    }

    public function testRenderMarksTheSubRequestEvenWhenTheTargetAttributesCarryTheMarkerOff(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [
                '_route' => 'oro_customer_frontend_customer_user_profile',
                EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => false
            ],
            []
        );

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $this->renderer->render(Request::create('/embedded/fragment/embedded_profile'), $target);

        self::assertTrue($capturedRequest->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE));
    }

    public function testRenderMarksTheSubRequestEvenWhenTheMainRequestCarriesTheMarkerOff(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $mainRequest = Request::create('/embedded/fragment/embedded_profile');
        $mainRequest->attributes->set(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE, false);

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (&$capturedRequest): Response {
                $capturedRequest = $request;

                return new Response();
            });

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile'],
            []
        );

        $this->renderer->render($mainRequest, $target);

        self::assertTrue($capturedRequest->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE));
        self::assertFalse($mainRequest->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE));
    }

    public function testRenderSetsRequestUriAndQueryStringServerParametersFromTheTargetUrl(): void
    {
        $this->embeddedRequestChecker
            ->method('isEmbeddedRequestReload')
            ->willReturn(false);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile?tab=orders',
            '/customer/profile',
            [],
            []
        );

        $capturedRequest = null;
        $this->httpKernel
            ->method('handle')
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
}
