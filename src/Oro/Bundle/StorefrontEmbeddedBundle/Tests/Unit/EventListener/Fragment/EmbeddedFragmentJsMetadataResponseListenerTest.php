<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentJsMetadataResponseListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedFragmentJsMetadataProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentJsMetadataResponseListenerTest extends TestCase
{
    private EmbeddedFragmentJsMetadataProvider&MockObject $jsMetadataProvider;

    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;

    private EmbeddedFragmentJsMetadataResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->jsMetadataProvider = $this->createMock(EmbeddedFragmentJsMetadataProvider::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->listener = new EmbeddedFragmentJsMetadataResponseListener(
            $this->jsMetadataProvider,
            $this->embeddedRequestChecker
        );
    }

    public function testOnFragmentResponseOverwritesContentValuesWithJsMetadataOnFirstRender(): void
    {
        $request = Request::create('/customer/profile');
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'runtimeUrl' => '/old.js',
            ]
        );

        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequestReload')
            ->with(self::identicalTo($request))
            ->willReturn(false);
        $this->jsMetadataProvider
            ->expects(self::once())
            ->method('getJsMetadata')
            ->willReturn(['runtimeUrl' => '/build/default/embedded_app.js', 'runtimeIntegrity' => 'sha384-abc']);

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'runtimeUrl' => '/build/default/embedded_app.js',
                'runtimeIntegrity' => 'sha384-abc',
            ],
            $event->getContent()
        );
    }

    public function testOnFragmentResponseDoesNothingOnHashNavigationReload(): void
    {
        $request = Request::create('/customer/shoppinglist/42');
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_shopping_list_view',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            ['embedded_shopping_list_view' => '<div>list</div>']
        );

        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequestReload')
            ->with(self::identicalTo($request))
            ->willReturn(true);
        $this->jsMetadataProvider
            ->expects(self::never())
            ->method('getJsMetadata');

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_shopping_list_view' => '<div>list</div>'], $event->getContent());
    }

    /**
     * @dataProvider onFragmentResponseDoesNothingForNonGetRequestProvider
     */
    public function testOnFragmentResponseDoesNothingForNonGetRequest(string $method): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile_update',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile/update', $method)],
            ['embedded_profile_update' => '<form>profile</form>']
        );

        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequestReload');
        $this->jsMetadataProvider
            ->expects(self::never())
            ->method('getJsMetadata');

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile_update' => '<form>profile</form>'], $event->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function onFragmentResponseDoesNothingForNonGetRequestProvider(): iterable
    {
        yield 'HEAD' => [Request::METHOD_HEAD];
        yield 'POST' => [Request::METHOD_POST];
        yield 'PUT' => [Request::METHOD_PUT];
    }

    public function testOnFragmentResponseDoesNothingWithoutRequest(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [],
            ['embedded_profile' => '<div>profile</div>']
        );

        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequestReload');
        $this->jsMetadataProvider
            ->expects(self::never())
            ->method('getJsMetadata');

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile' => '<div>profile</div>'], $event->getContent());
    }
}
