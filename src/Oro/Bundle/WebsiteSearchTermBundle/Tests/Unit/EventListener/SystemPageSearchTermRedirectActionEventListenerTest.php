<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\SystemPageSearchTermRedirectActionEventListener;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SystemPageSearchTermRedirectActionEventListenerTest extends TestCase
{
    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;

    private UrlGeneratorInterface|MockObject $urlGenerator;

    private SystemPageSearchTermRedirectActionEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->listener = new SystemPageSearchTermRedirectActionEventListener(
            $this->basicRedirectActionHandler,
            $this->urlGenerator
        );
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTerm())
            ->setRedirectActionType('system_page');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotSystemPage(): void
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

    public function testWhenNoRedirectSystemPage(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('system_page');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasRedirectSystemPage(): void
    {
        $redirectSystemPage = 'sample_route';
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('system_page')
            ->setRedirectSystemPage($redirectSystemPage);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $redirectUrl = '/sample-page';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($redirectSystemPage)
            ->willReturn($redirectUrl);

        $request = new Request();
        $requestEvent
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new Response('system page content');
        $this->basicRedirectActionHandler
            ->expects(self::once())
            ->method('getResponse')
            ->with($request, $searchTerm, $redirectUrl)
            ->willReturn($response);

        $requestEvent
            ->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
