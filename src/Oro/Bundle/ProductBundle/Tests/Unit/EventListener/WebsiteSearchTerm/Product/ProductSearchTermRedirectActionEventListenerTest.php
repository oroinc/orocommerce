<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\Product;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\Product\ProductSearchTermRedirectActionEventListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductSearchTermRedirectActionEventListenerTest extends TestCase
{
    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;

    private UrlGeneratorInterface|MockObject $urlGenerator;

    private ProductSearchTermRedirectActionEventListener $listener;

    protected function setUp(): void
    {
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->listener = new ProductSearchTermRedirectActionEventListener(
            $this->basicRedirectActionHandler,
            $this->urlGenerator
        );
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setRedirectActionType('product');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotProduct(): void
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

    public function testWhenNoRedirectProduct(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('product');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasRedirectProduct(): void
    {
        $slug = (new Slug())->setUrl('sample/url');
        $redirectProduct = (new ProductStub())
            ->setId(42)
            ->addSlug($slug);
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('product')
            ->setRedirectProduct($redirectProduct);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $productUrl = '/sample-page';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                'oro_product_frontend_product_view',
                ['id' => $redirectProduct->getId()]
            )
            ->willReturn($productUrl);

        $request = new Request();
        $requestEvent
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new Response('product page content');
        $this->basicRedirectActionHandler
            ->expects(self::once())
            ->method('getResponse')
            ->with($request, $searchTerm, $productUrl)
            ->willReturn($response);

        $requestEvent
            ->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
