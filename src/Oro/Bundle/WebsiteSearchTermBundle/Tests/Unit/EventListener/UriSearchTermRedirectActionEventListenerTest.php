<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\UriSearchTermRedirectActionEventListener;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UriSearchTermRedirectActionEventListenerTest extends TestCase
{
    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;

    private UriSearchTermRedirectActionEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->listener = new UriSearchTermRedirectActionEventListener($this->basicRedirectActionHandler);
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTerm())
            ->setRedirectActionType('uri');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotUri(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('invalid');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenNoRedirectUri(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasRedirectUri(): void
    {
        $redirectUri = 'http://example.com';
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirectUri($redirectUri);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $request = new Request();
        $requestEvent
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new RedirectResponse($redirectUri, Response::HTTP_MOVED_PERMANENTLY);
        $this->basicRedirectActionHandler
            ->expects(self::once())
            ->method('getResponse')
            ->with($request, $searchTerm, $redirectUri)
            ->willReturn($response);

        $requestEvent
            ->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
