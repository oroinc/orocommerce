<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentResponseDecorator;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTarget;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRuntimeBootProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class EmbeddedFragmentResponseDecoratorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmbeddedRuntimeBootProvider&MockObject $runtimeBootProvider;

    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;

    private EmbeddedFragmentResponseDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->runtimeBootProvider = $this->createMock(EmbeddedRuntimeBootProvider::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->decorator = new EmbeddedFragmentResponseDecorator(
            $this->runtimeBootProvider,
            $this->embeddedRequestChecker
        );
        $this->setUpLoggerMock($this->decorator);
    }

    public function testDecorateNeitherSetsThePathHeaderNorTouchesTheBodyOfAPostResponse(): void
    {
        $response = new Response('unchanged body');

        $this->embeddedRequestChecker

            ->expects(self::never())
            ->method('isEmbeddedRequestReload');
        $this->runtimeBootProvider
            ->expects(self::never())
            ->method('getBootMetadata');

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate(Request::create('/', Request::METHOD_POST), $response, $target);

        self::assertSame('unchanged body', $response->getContent());
        self::assertFalse($response->headers->has('X-Oro-Embedded-Path'));
    }

    public function testDecorateAddsFlashMessagesSubtreeUrlAndBootMetadataOnTheFirstGetRender(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', Request::METHOD_GET);
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved.');
        $request->setSession($session);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(false);
        $this->runtimeBootProvider
            ->expects(self::once())
            ->method('getBootMetadata')
            ->willReturn(['runtimeUrl' => '/build/theme/embedded_app.js']);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate($request, $response, $target);

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('bar', $payload['foo']);
        self::assertSame(['success' => ['Saved.']], $payload['flashMessages']);
        self::assertSame('/customer/profile', $payload['subtreeUrl']);
        self::assertSame('/build/theme/embedded_app.js', $payload['runtimeUrl']);
    }

    public function testDecorateAddsSubtreeUrlButDoesNotMergeBootMetadataOnAHashNavigationReload(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', Request::METHOD_GET);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(true);
        $this->runtimeBootProvider
            ->expects(self::never())
            ->method('getBootMetadata');

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate($request, $response, $target);

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('/customer/profile', $payload['subtreeUrl']);
        self::assertArrayNotHasKey('runtimeUrl', $payload);
    }

    public function testDecorateReplacesTheRenderedTitleBlockWithItsPlainText(): void
    {
        $response = new JsonResponse(['title' => "  <title>Shopping List &amp; Co &quot;A&quot;</title>\n"]);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate(Request::create('/', Request::METHOD_GET), $response, $target);

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('Shopping List & Co "A"', $payload['title']);
    }

    public function testDecorateSetsAnEmptyTitleWhenTheTitleBlockWasNotRendered(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate(Request::create('/', Request::METHOD_GET), $response, $target);

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('', $payload['title']);
    }

    public function testDecorateReturnsEmptyFlashMessagesWhenTheRequestHasNoSession(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', Request::METHOD_GET);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate($request, $response, $target);

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame([], $payload['flashMessages']);
    }

    public function testDecorateLogsAndDoesNotThrowWhenTheBodyIsNotLayoutJson(): void
    {
        $response = new Response('<html>not json</html>');
        $request = Request::create('/', Request::METHOD_GET);

        $this->embeddedRequestChecker

            ->expects(self::never())
            ->method('isEmbeddedRequestReload');
        $this->assertLoggerDebugMethodCalled();

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate($request, $response, $target);

        self::assertSame('<html>not json</html>', $response->getContent());
    }

    public function testDecorateEscapesHtmlSensitiveCharactersWhenReplacingAPlainResponseBody(): void
    {
        $response = new Response(json_encode(['label' => '<script>alert(1)</script>']));
        $request = Request::create('/', Request::METHOD_GET);

        $this->embeddedRequestChecker

            ->method('isEmbeddedRequestReload')
            ->willReturn(true);

        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );

        $this->decorator->decorate($request, $response, $target);

        $content = (string) $response->getContent();
        self::assertStringNotContainsString('<script>', $content);

        $payload = json_decode($content, true);
        self::assertSame('<script>alert(1)</script>', $payload['label']);
    }
}
