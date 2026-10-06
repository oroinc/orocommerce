<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Kernel;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel\EmbeddedFragmentResponseListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EmbeddedFragmentResponseListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private EmbeddedFragmentResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new EmbeddedFragmentResponseListener(
            $this->eventDispatcher,
            new EmbeddedRequestChecker(new RequestStack())
        );
        $this->setUpLoggerMock($this->listener);
    }

    public function testOnKernelResponseWritesChangedContentToBody(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new JsonResponse(['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>']);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmbeddedFragmentResponseEvent $event) use ($request): EmbeddedFragmentResponseEvent {
                    self::assertSame('embedded_profile', $event->getFragmentName());
                    self::assertSame(
                        ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>'],
                        $event->getContent()
                    );
                    self::assertSame(
                        [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
                        $event->getContext()
                    );

                    $event->setContentValue('title', 'Profile');
                    $event->setContentValue('subtreeUrl', '/customer/profile');

                    return $event;
                }
            );

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame(
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'subtreeUrl' => '/customer/profile',
            ],
            json_decode((string)$response->getContent(), true)
        );
    }

    public function testOnKernelResponseKeepsHexEscapingForJsonResponse(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new JsonResponse(['embedded_profile' => '<div>profile</div>']);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (EmbeddedFragmentResponseEvent $event): EmbeddedFragmentResponseEvent {
                $event->setContentValue('label', '<script>alert(1)</script>');

                return $event;
            });

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        $content = (string)$response->getContent();
        self::assertStringNotContainsString('<script>', $content);
        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'label' => '<script>alert(1)</script>'],
            json_decode($content, true)
        );
    }

    public function testOnKernelResponseEscapesHtmlCharactersForPlainResponse(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new Response(json_encode(['embedded_profile' => '<div>profile</div>']));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (EmbeddedFragmentResponseEvent $event): EmbeddedFragmentResponseEvent {
                $event->setContentValue('label', '<script>alert(1)</script>');

                return $event;
            });

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        $content = (string)$response->getContent();
        self::assertStringNotContainsString('<script>', $content);
        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'label' => '<script>alert(1)</script>'],
            json_decode($content, true)
        );
    }

    /**
     * @dataProvider onKernelResponseLogsErrorForInvalidJsonBodyProvider
     */
    public function testOnKernelResponseLogsErrorForInvalidJsonBody(string $content): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/html']);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Embedded fragment body could not be decoded, metadata was not added.',
                self::callback(static function (array $context): bool {
                    self::assertSame('embedded_profile', $context['fragment']);
                    self::assertSame(Response::HTTP_OK, $context['status_code']);
                    self::assertSame('text/html', $context['content_type']);
                    self::assertInstanceOf(\JsonException::class, $context['exception']);

                    return true;
                })
            );

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame($content, $response->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function onKernelResponseLogsErrorForInvalidJsonBodyProvider(): iterable
    {
        yield 'html' => ['<html>not json</html>'];
        yield 'empty body' => [''];
    }

    /**
     * @dataProvider onKernelResponseLogsDebugForNonArrayJsonBodyProvider
     */
    public function testOnKernelResponseLogsDebugForNonArrayJsonBody(string $content): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/html']);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');
        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Embedded fragment body was not layout JSON, metadata was not added.',
                ['fragment' => 'embedded_profile', 'status_code' => Response::HTTP_OK, 'content_type' => 'text/html']
            );

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame($content, $response->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function onKernelResponseLogsDebugForNonArrayJsonBodyProvider(): iterable
    {
        yield 'json scalar' => ['"text"'];
        yield 'json null' => ['null'];
    }

    public function testOnKernelResponseKeepsJsonResponseBodyWhenEncodingFails(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new JsonResponse(['embedded_profile' => '<div>profile</div>']);
        $originalContent = $response->getContent();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmbeddedFragmentResponseEvent $event): EmbeddedFragmentResponseEvent {
                    $event->setContentValue('extra', "Saved \xB1");

                    return $event;
                }
            );
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Embedded fragment response content could not be encoded, the original body was kept.',
                self::callback(static function (array $context): bool {
                    self::assertSame('embedded_profile', $context['fragment']);
                    self::assertInstanceOf(\InvalidArgumentException::class, $context['exception']);

                    return true;
                })
            );

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame($originalContent, $response->getContent());
    }

    public function testOnKernelResponseKeepsPlainResponseBodyWhenEncodingFails(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new Response('{"embedded_profile":"\u003Cdiv\u003Eprofile\u003C\/div\u003E"}');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmbeddedFragmentResponseEvent $event): EmbeddedFragmentResponseEvent {
                    $event->setContentValue('extra', "Saved \xB1");

                    return $event;
                }
            );
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Embedded fragment response content could not be encoded, the original body was kept.',
                self::callback(static function (array $context): bool {
                    self::assertSame('embedded_profile', $context['fragment']);
                    self::assertInstanceOf(\JsonException::class, $context['exception']);

                    return true;
                })
            );

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame(
            '{"embedded_profile":"\u003Cdiv\u003Eprofile\u003C\/div\u003E"}',
            $response->getContent()
        );
    }

    public function testOnKernelResponseDoesNothingForNonEmbeddedFragmentRequest(): void
    {
        $response = new JsonResponse(['page_main_content' => '<div>page</div>']);
        $originalContent = $response->getContent();

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                Request::create('/customer/profile'),
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame($originalContent, $response->getContent());
    }

    public function testOnKernelResponseDoesNothingForSubRequest(): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new JsonResponse(['embedded_profile' => '<div>profile</div>']);
        $originalContent = $response->getContent();

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::SUB_REQUEST,
                $response
            )
        );

        self::assertSame($originalContent, $response->getContent());
    }

    /**
     * @dataProvider onKernelResponseDoesNothingForUnsuccessfulResponseProvider
     */
    public function testOnKernelResponseDoesNothingForUnsuccessfulResponse(int $statusCode): void
    {
        $request = Request::create('/customer/profile');
        $request->attributes->set(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME, 'embedded_profile');
        $response = new JsonResponse(['page_main_content' => 'error'], $statusCode);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelResponse(
            new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            )
        );

        self::assertSame('{"page_main_content":"error"}', $response->getContent());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function onKernelResponseDoesNothingForUnsuccessfulResponseProvider(): iterable
    {
        yield 'first redirect status' => [Response::HTTP_MULTIPLE_CHOICES];
        yield 'server error' => [Response::HTTP_INTERNAL_SERVER_ERROR];
    }
}
