<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentFlashMessagesResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class EmbeddedFragmentFlashMessagesResponseListenerTest extends TestCase
{
    private EmbeddedFragmentFlashMessagesResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new EmbeddedFragmentFlashMessagesResponseListener();
    }

    public function testOnFragmentResponseTakesFlashMessagesFromSession(): void
    {
        $request = Request::create('/customer/profile');
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved.');
        $session->getFlashBag()->add('error', 'Failed.');
        $request->setSession($session);
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile' => '<div>profile</div>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            [
                'embedded_profile' => '<div>profile</div>',
                'flashMessages' => ['success' => ['Saved.'], 'error' => ['Failed.']],
            ],
            $event->getContent()
        );
        self::assertSame([], $session->getFlashBag()->peekAll());
    }

    public function testOnFragmentResponseSetsEmptyFlashMessagesWithoutSession(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'flashMessages' => []],
            $event->getContent()
        );
    }

    public function testOnFragmentResponseSetsEmptyFlashMessagesWhenSessionHasNoFlashBag(): void
    {
        $request = Request::create('/customer/profile');
        $request->setSession($this->createMock(SessionInterface::class));
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile' => '<div>profile</div>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(
            ['embedded_profile' => '<div>profile</div>', 'flashMessages' => []],
            $event->getContent()
        );
    }

    /**
     * @dataProvider onFragmentResponseKeepsFlashMessagesForNonGetRequestProvider
     */
    public function testOnFragmentResponseKeepsFlashMessagesForNonGetRequest(string $method): void
    {
        $request = Request::create('/customer/profile/update', $method);
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved.');
        $request->setSession($session);
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile_update',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile_update' => '<form>profile</form>']
        );

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile_update' => '<form>profile</form>'], $event->getContent());
        self::assertSame(['success' => ['Saved.']], $session->getFlashBag()->peekAll());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function onFragmentResponseKeepsFlashMessagesForNonGetRequestProvider(): iterable
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

        $this->listener->onFragmentResponse($event);

        self::assertSame(['embedded_profile' => '<div>profile</div>'], $event->getContent());
    }
}
