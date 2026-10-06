<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentStylesMetadataResponseListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedFragmentStylesMetadataProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentStylesMetadataResponseListenerTest extends TestCase
{
    private EmbeddedFragmentStylesMetadataProvider&MockObject $stylesMetadataProvider;

    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;

    private EmbeddedFragmentStylesMetadataResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->stylesMetadataProvider = $this->createMock(EmbeddedFragmentStylesMetadataProvider::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->listener = new EmbeddedFragmentStylesMetadataResponseListener(
            $this->stylesMetadataProvider,
            $this->embeddedRequestChecker
        );
    }

    public function testOnFragmentResponseOverwritesContentValuesWithStylesMetadataOnFirstRender(): void
    {
        $request = Request::create('/customer/profile');
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'styles' => ['/old.css'],
            ]
        );

        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequestReload')
            ->with(self::identicalTo($request))
            ->willReturn(false);
        $this->stylesMetadataProvider
            ->expects(self::once())
            ->method('getStylesMetadata')
            ->willReturn(['styles' => ['/build/default/css/styles.css'], 'printStyles' => []]);

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'styles' => ['/build/default/css/styles.css'],
                'printStyles' => [],
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
        $this->stylesMetadataProvider
            ->expects(self::never())
            ->method('getStylesMetadata');

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
        $this->stylesMetadataProvider
            ->expects(self::never())
            ->method('getStylesMetadata');

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
        $this->stylesMetadataProvider
            ->expects(self::never())
            ->method('getStylesMetadata');

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile' => '<div>profile</div>'], $event->getContent());
    }
}
