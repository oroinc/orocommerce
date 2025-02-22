<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page\PageSearchTermRedirectActionEventListener;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\CMSBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageSearchTermRedirectActionEventListenerTest extends TestCase
{
    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;

    private UrlGeneratorInterface|MockObject $urlGenerator;

    private PageSearchTermRedirectActionEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->listener = new PageSearchTermRedirectActionEventListener(
            $this->basicRedirectActionHandler,
            $this->urlGenerator
        );
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setRedirectActionType('cms_page');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotCmsPage(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenNoRedirectPage(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('cms_page');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasRedirectPage(): void
    {
        $slug = (new Slug())->setUrl('sample/url');
        $redirectCmsPage = (new Page(42))
            ->addSlug($slug);
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('cms_page')
            ->setRedirectCmsPage($redirectCmsPage);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $pageUrl = '/sample-page';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('oro_cms_frontend_page_view', ['id' => $redirectCmsPage->getId()])
            ->willReturn($pageUrl);

        $request = new Request();
        $requestEvent
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new Response('page content');
        $this->basicRedirectActionHandler
            ->expects(self::once())
            ->method('getResponse')
            ->with($request, $searchTerm, $pageUrl)
            ->willReturn($response);

        $requestEvent
            ->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
