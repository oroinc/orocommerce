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

    private const string BLOCK = 'embedded_profile';
    private const string ROUTE_NAME = 'oro_customer_frontend_customer_user_profile';

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

    public function testDecorateSetsThePathHeaderOnEveryResponse(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);

        $this->decorator->decorate(Request::create('/', 'GET'), $response, $this->createTarget());

        self::assertSame('/customer/profile', $response->headers->get(EmbeddedFragmentResponseDecorator::PATH_HEADER));
    }

    public function testDecorateSetsThePathHeaderOnAPostResponseWithoutTouchingTheBody(): void
    {
        $response = new Response('unchanged body');

        $this->embeddedRequestChecker->expects(self::never())
            ->method('isFragmentReload');
        $this->runtimeBootProvider->expects(self::never())
            ->method('getBootMetadata');

        $this->decorator->decorate(Request::create('/', 'POST'), $response, $this->createTarget());

        self::assertSame('unchanged body', $response->getContent());
        self::assertSame('/customer/profile', $response->headers->get(EmbeddedFragmentResponseDecorator::PATH_HEADER));
    }

    public function testDecorateAddsFlashMessagesAndBootMetadataOnTheFirstGetRender(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', 'GET');
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved.');
        $request->setSession($session);

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(false);
        $this->runtimeBootProvider->expects(self::once())
            ->method('getBootMetadata')
            ->with($request, self::BLOCK)
            ->willReturn(['buildBase' => '/build/theme/']);

        $this->decorator->decorate($request, $response, $this->createTarget());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('bar', $payload['foo']);
        self::assertSame(['success' => ['Saved.']], $payload[EmbeddedFragmentResponseDecorator::FLASH_MESSAGES_KEY]);
        self::assertSame('/build/theme/', $payload['buildBase']);
    }

    public function testDecorateDoesNotMergeBootMetadataOnAFragmentReload(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', 'GET');

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);
        $this->runtimeBootProvider->expects(self::never())
            ->method('getBootMetadata');

        $this->decorator->decorate($request, $response, $this->createTarget());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertArrayNotHasKey('buildBase', $payload);
    }

    public function testDecorateReplacesTheRenderedTitleBlockWithItsPlainText(): void
    {
        $response = new JsonResponse(['title' => "  <title>Shopping List &amp; Co &quot;A&quot;</title>\n"]);

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $this->decorator->decorate(Request::create('/', 'GET'), $response, $this->createTarget());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('Shopping List & Co "A"', $payload[EmbeddedFragmentResponseDecorator::TITLE_KEY]);
    }

    public function testDecorateSetsAnEmptyTitleWhenTheTitleBlockWasNotRendered(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $this->decorator->decorate(Request::create('/', 'GET'), $response, $this->createTarget());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('', $payload[EmbeddedFragmentResponseDecorator::TITLE_KEY]);
    }

    public function testDecorateReturnsEmptyFlashMessagesWhenTheRequestHasNoSession(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $request = Request::create('/', 'GET');

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $this->decorator->decorate($request, $response, $this->createTarget());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame([], $payload[EmbeddedFragmentResponseDecorator::FLASH_MESSAGES_KEY]);
    }

    public function testDecorateLogsAndDoesNotThrowWhenTheBodyIsNotLayoutJson(): void
    {
        $response = new Response('<html>not json</html>');
        $request = Request::create('/', 'GET');

        $this->embeddedRequestChecker->expects(self::never())
            ->method('isFragmentReload');
        $this->assertLoggerDebugMethodCalled();

        $this->decorator->decorate($request, $response, $this->createTarget());

        self::assertSame('<html>not json</html>', $response->getContent());
    }

    public function testDecorateEscapesHtmlSensitiveCharactersWhenReplacingAPlainResponseBody(): void
    {
        $response = new Response(json_encode(['label' => '<script>alert(1)</script>']));
        $request = Request::create('/', 'GET');

        $this->embeddedRequestChecker->method('isFragmentReload')
            ->willReturn(true);

        $this->decorator->decorate($request, $response, $this->createTarget());

        $content = (string) $response->getContent();
        self::assertStringNotContainsString('<script>', $content);

        $payload = json_decode($content, true);
        self::assertSame('<script>alert(1)</script>', $payload['label']);
    }

    private function createTarget(): EmbeddedFragmentTarget
    {
        return new EmbeddedFragmentTarget(
            self::BLOCK,
            self::ROUTE_NAME,
            'https://storefront.example.test/customer/profile',
            '/customer/profile',
            [],
            []
        );
    }
}
