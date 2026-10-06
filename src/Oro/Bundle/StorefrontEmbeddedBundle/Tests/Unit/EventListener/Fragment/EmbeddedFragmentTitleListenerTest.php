<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentTitleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentTitleListenerTest extends TestCase
{
    private EmbeddedFragmentTitleListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new EmbeddedFragmentTitleListener();
    }

    /**
     * @dataProvider onFragmentResolveAddsTitleBlockOnlyForGetRequestProvider
     *
     * @param string[] $expectedBlockIds
     */
    public function testOnFragmentResolveAddsTitleBlockOnlyForGetRequest(string $method, array $expectedBlockIds): void
    {
        $request = Request::create('/embedded/fragment/embedded_profile', $method);
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            [],
            [EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile']
        );

        $this->listener->onFragmentResolve($event);

        self::assertSame($expectedBlockIds, $event->getBlockIds());
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function onFragmentResolveAddsTitleBlockOnlyForGetRequestProvider(): iterable
    {
        yield 'GET' => [Request::METHOD_GET, ['embedded_profile', 'title']];
        yield 'HEAD' => [Request::METHOD_HEAD, ['embedded_profile']];
        yield 'POST' => [Request::METHOD_POST, ['embedded_profile']];
        yield 'PUT' => [Request::METHOD_PUT, ['embedded_profile']];
    }

    public function testOnFragmentResolveDoesNothingWithoutRequest(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            [],
            [],
            ['embedded_profile']
        );

        $this->listener->onFragmentResolve($event);

        self::assertSame(['embedded_profile'], $event->getBlockIds());
    }

    public function testOnFragmentResponseReplacesTitleBlockWithPlainText(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_shopping_list_view',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/shoppinglist/42')],
            [
                'embedded_shopping_list_view' => '<div>list</div>',
                'title' => "  <title>Shopping List &amp; Co &quot;A&quot;</title>\n",
            ]
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            ['embedded_shopping_list_view' => '<div>list</div>', 'title' => 'Shopping List & Co "A"'],
            $event->getContent()
        );
    }

    public function testOnFragmentResponseSetsEmptyTitleWhenContentHasNoTitleBlock(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile' => '<div>profile</div>', 'title' => ''], $event->getContent());
    }

    public function testOnFragmentResponseSetsEmptyTitleWhenTitleIsNotString(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>', 'title' => ['<title>Profile</title>']]
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile' => '<div>profile</div>', 'title' => ''], $event->getContent());
    }

    /**
     * @dataProvider onFragmentResponseDoesNothingForNonGetRequestProvider
     */
    public function testOnFragmentResponseDoesNothingForNonGetRequest(string $method): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile', $method)],
            ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>'],
            $event->getContent()
        );
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
            ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>'],
            $event->getContent()
        );
    }
}
