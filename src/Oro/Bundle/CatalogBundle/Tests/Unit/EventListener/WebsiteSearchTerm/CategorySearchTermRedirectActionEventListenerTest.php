<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm\CategorySearchTermRedirectActionEventListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CategorySearchTermRedirectActionEventListenerTest extends TestCase
{
    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private CategorySearchTermRedirectActionEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->listener = new CategorySearchTermRedirectActionEventListener(
            $this->basicRedirectActionHandler,
            $this->urlGenerator
        );
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setRedirectActionType('category');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotCategory(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenNoRedirectCategory(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('category');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasRedirectCategory(): void
    {
        $redirectCategory = new CategoryStub();
        ReflectionUtil::setId($redirectCategory, 42);

        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('category')
            ->setRedirectCategory($redirectCategory);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $categoryUrl = '/sample-page';
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_index')
            ->willReturn($categoryUrl);

        $request = new Request();
        $request->query->set('search', 'sample_phrase');
        $requestEvent->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new Response('category page content');
        $this->basicRedirectActionHandler->expects(self::once())
            ->method('getResponse')
            ->with(
                $request,
                $searchTerm,
                $categoryUrl,
                [
                    'search' => 'sample_phrase',
                    'frontend-product-search-grid' => [
                        SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                    ],
                    'categoryId' => $redirectCategory->getId(),
                    'includeSubcategories' => true,
                ]
            )
            ->willReturn($response);

        $requestEvent->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
