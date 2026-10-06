<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Kernel;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel\EmbeddedFragmentRequestListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentUrlResolver;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmbeddedFragmentRequestListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmbeddedFragmentUrlResolver&MockObject $embeddedFragmentUrlResolver;

    private EmbeddedFragmentRequestListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->embeddedFragmentUrlResolver = $this->createMock(EmbeddedFragmentUrlResolver::class);

        $this->listener = new EmbeddedFragmentRequestListener($this->embeddedFragmentUrlResolver, '/embedded/fragment');
        $this->setUpLoggerMock($this->listener);
    }

    public function testDoesNothingForSubRequest(): void
    {
        $request = Request::create('/embedded/fragment/embedded_profile');

        $this->embeddedFragmentUrlResolver
            ->expects(self::never())
            ->method('resolveFragmentUrl');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        ));

        self::assertFalse($request->attributes->has(EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME));
    }

    /**
     * @dataProvider doesNothingForOtherPathProvider
     */
    public function testDoesNothingForOtherPath(string $uri, string $method): void
    {
        $request = Request::create($uri, $method);

        $this->embeddedFragmentUrlResolver
            ->expects(self::never())
            ->method('resolveFragmentUrl');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame([], $request->attributes->all());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function doesNothingForOtherPathProvider(): iterable
    {
        yield 'storefront page' => ['/customer/profile', Request::METHOD_GET];
        yield 'storefront page with PATCH method' => ['/customer/profile', Request::METHOD_PATCH];
        yield 'fragment path without name' => ['/embedded/fragment/', Request::METHOD_GET];
        yield 'name with extra path segment' => ['/embedded/fragment/embedded_profile/extra', Request::METHOD_GET];
        yield 'name with encoded slash' => ['/embedded/fragment/embedded%2Fprofile', Request::METHOD_GET];
        yield 'fragment path under prefix' => ['/prefix/embedded/fragment/embedded_profile', Request::METHOD_GET];
    }

    /**
     * @dataProvider passesAnyFragmentNameToResolverProvider
     */
    public function testPassesAnyFragmentNameToResolver(
        string $uri,
        string $fragmentName
    ): void {
        $this->embeddedFragmentUrlResolver
            ->expects(self::once())
            ->method('resolveFragmentUrl')
            ->with($fragmentName)
            ->willThrowException(new NotFoundHttpException());

        $this->expectException(NotFoundHttpException::class);

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($uri),
            HttpKernelInterface::MAIN_REQUEST
        ));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function passesAnyFragmentNameToResolverProvider(): iterable
    {
        yield 'name without embedded prefix' => ['/embedded/fragment/not_embedded_name', 'not_embedded_name'];
        yield 'name followed by encoded newline' => ['/embedded/fragment/embedded_profile%0A', "embedded_profile\n"];
    }

    public function testThrowsExceptionForDisallowedMethod(): void
    {
        $this->embeddedFragmentUrlResolver
            ->expects(self::never())
            ->method('resolveFragmentUrl');

        try {
            $this->listener->onKernelRequest(new RequestEvent(
                $this->createMock(HttpKernelInterface::class),
                Request::create('/embedded/fragment/embedded_profile', Request::METHOD_PATCH),
                HttpKernelInterface::MAIN_REQUEST
            ));
            self::fail('MethodNotAllowedHttpException was not thrown.');
        } catch (MethodNotAllowedHttpException $exception) {
            self::assertSame('The embedded fragment does not support the PATCH method.', $exception->getMessage());
            self::assertSame(['Allow' => 'HEAD, GET, POST, PUT'], $exception->getHeaders());
        }
    }

    public function testResolvesUrlEncodedFragmentName(): void
    {
        $request = Request::create('/embedded/fragment/embedded%5Fprofile');

        $this->embeddedFragmentUrlResolver
            ->expects(self::once())
            ->method('resolveFragmentUrl')
            ->with('embedded_profile', [])
            ->willReturn('/customer/profile');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame('/customer/profile', $request->getPathInfo());
    }

    public function testReplacesQueryWithResolvedUrlQuery(): void
    {
        $request = Request::create(
            '/embedded/fragment/embedded_shopping_list_view',
            Request::METHOD_GET,
            ['layout_block_ids' => ['page_main_content'], 'id' => '42', 'grid' => ['a' => 'b']]
        );

        $this->embeddedFragmentUrlResolver
            ->expects(self::once())
            ->method('resolveFragmentUrl')
            ->with(
                'embedded_shopping_list_view',
                ['layout_block_ids' => ['page_main_content'], 'id' => '42', 'grid' => ['a' => 'b']]
            )
            ->willReturn(
                '/customer/shoppinglist/42?grid%5Ba%5D=b'
                . '&layout_block_ids%5B0%5D=embedded_shopping_list_view&layout_block_ids%5B1%5D=title'
            );

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame(
            ['grid' => ['a' => 'b'], 'layout_block_ids' => ['embedded_shopping_list_view', 'title']],
            $request->query->all()
        );
    }

    public function testPassesFilteredQueryAndRequestContextToResolver(): void
    {
        $request = Request::create(
            '/embedded/fragment/embedded_profile',
            Request::METHOD_GET,
            ['tab' => 'orders', '_csrf_token' => 'abc', '_widgetContainer' => 'dialog', 0 => 'positional']
        );

        $this->embeddedFragmentUrlResolver
            ->expects(self::once())
            ->method('resolveFragmentUrl')
            ->with(
                'embedded_profile',
                ['tab' => 'orders', 0 => 'positional'],
                self::identicalTo([EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request])
            )
            ->willReturn('/customer/profile?tab=orders&0=positional');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));
    }

    public function testPointsRequestUriAndQueryStringToResolvedUrl(): void
    {
        $request = Request::create('/embedded/fragment/embedded_shopping_lists?grid%5Ba%5D=b');

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/shoppinglist/?grid%5Ba%5D=b');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame('/customer/shoppinglist/?grid%5Ba%5D=b', $request->server->get('REQUEST_URI'));
        self::assertSame('grid%5Ba%5D=b', $request->server->get('QUERY_STRING'));
        self::assertSame('/customer/shoppinglist/', $request->getPathInfo());
    }

    public function testSetsEmptyQueryStringWhenResolvedUrlHasNoQuery(): void
    {
        $request = Request::create('/embedded/fragment/embedded_profile?foo=bar');

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/profile');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame('', $request->server->get('QUERY_STRING'));
        self::assertSame('/customer/profile', $request->getRequestUri());
    }

    public function testKeepsAttributesExceptControllerAndSetsFragmentName(): void
    {
        $request = Request::create('/embedded/fragment/embedded_customer_user_role_view');
        $request->attributes->add([
            '_controller' => 'Inherited\\Controller::action',
            '_locale' => 'en',
            '_firewall_context' => 'security.firewall.map.context.frontend',
            'id' => 'inherited',
        ]);

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/roles/view/7');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame(
            [
                '_locale' => 'en',
                '_firewall_context' => 'security.firewall.map.context.frontend',
                'id' => 'inherited',
                EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME => 'embedded_customer_user_role_view',
            ],
            $request->attributes->all()
        );
    }

    public function testKeepsSessionMethodBodyCookiesAndServerHeaders(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create(
            '/embedded/fragment/embedded_profile',
            Request::METHOD_POST,
            ['form' => ['label' => 'Submitted']],
            ['OROSFID' => 'session-id'],
            [],
            ['HTTP_X_ORO_EMBEDDED' => '1'],
            'form%5Blabel%5D=Submitted'
        );
        $request->setSession($session);

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/profile');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame($session, $request->getSession());
        self::assertSame(Request::METHOD_POST, $request->getMethod());
        self::assertSame(['form' => ['label' => 'Submitted']], $request->request->all());
        self::assertSame('form%5Blabel%5D=Submitted', $request->getContent());
        self::assertSame(['OROSFID' => 'session-id'], $request->cookies->all());
        self::assertSame('1', $request->headers->get(EmbeddedRequestChecker::EMBEDDED_HEADER));
    }

    public function testKeepsUploadedFile(): void
    {
        $uploadedFile = new UploadedFile(__FILE__, 'avatar.png', 'image/png', \UPLOAD_ERR_OK, true);
        $request = Request::create(
            '/embedded/fragment/embedded_profile',
            Request::METHOD_POST,
            [],
            [],
            ['form' => ['avatar' => $uploadedFile]]
        );

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/profile');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame(['form' => ['avatar' => $uploadedFile]], $request->files->all());
    }

    public function testPointsPathInfoToResolvedPathBehindFrontController(): void
    {
        $request = Request::create(
            '/index_dev.php/embedded/fragment/embedded_profile',
            Request::METHOD_GET,
            [],
            [],
            [],
            [
                'SCRIPT_NAME' => '/index_dev.php',
                'SCRIPT_FILENAME' => '/var/www/public/index_dev.php',
                'PHP_SELF' => '/index_dev.php/embedded/fragment/embedded_profile',
            ]
        );

        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/index_dev.php/customer/profile');

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame('/customer/profile', $request->getPathInfo());
        self::assertSame('/index_dev.php/customer/profile', $request->getRequestUri());
    }

    public function testLogsResolvedUrl(): void
    {
        $this->embeddedFragmentUrlResolver
            ->method('resolveFragmentUrl')
            ->willReturn('/customer/profile');
        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Embedded fragment request rewritten to "{fragmentUrl}".',
                [
                    'fragmentName' => 'embedded_profile',
                    'fragmentUrl' => '/customer/profile',
                    'requestMethod' => Request::METHOD_POST,
                ]
            );

        $this->listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/embedded/fragment/embedded_profile', Request::METHOD_POST),
            HttpKernelInterface::MAIN_REQUEST
        ));
    }

    public function testResolvesFragmentUnderConfiguredFragmentPath(): void
    {
        $listener = new EmbeddedFragmentRequestListener($this->embeddedFragmentUrlResolver, '/custom.fragment');
        $request = Request::create('/custom.fragment/embedded_profile');

        $this->embeddedFragmentUrlResolver
            ->expects(self::once())
            ->method('resolveFragmentUrl')
            ->with('embedded_profile', [])
            ->willReturn('/customer/profile');

        $listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame('/customer/profile', $request->getPathInfo());
    }

    /**
     * @dataProvider doesNothingForPathOutsideConfiguredFragmentPathProvider
     */
    public function testDoesNothingForPathOutsideConfiguredFragmentPath(string $uri): void
    {
        $listener = new EmbeddedFragmentRequestListener($this->embeddedFragmentUrlResolver, '/custom.fragment');
        $request = Request::create($uri);

        $this->embeddedFragmentUrlResolver
            ->expects(self::never())
            ->method('resolveFragmentUrl');

        $listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertSame([], $request->attributes->all());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function doesNothingForPathOutsideConfiguredFragmentPathProvider(): iterable
    {
        yield 'default fragment path' => ['/embedded/fragment/embedded_profile'];
        yield 'dot of the fragment path taken as any character' => ['/customXfragment/embedded_profile'];
    }
}
