<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\RedirectActionTypeSearchPageKernelListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RedirectActionTypeSearchPageKernelListenerTest extends TestCase
{
    private SearchTermProvider|MockObject $searchTermProvider;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private RedirectActionTypeSearchPageKernelListener $listener;

    private FeatureChecker|MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchTermProvider = $this->createMock(SearchTermProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new RedirectActionTypeSearchPageKernelListener(
            $this->searchTermProvider,
            $this->eventDispatcher
        );

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->addFeature('oro_website_search_terms_management');
        $this->listener->setFeatureChecker($this->featureChecker);
    }

    public function testWhenNotSearchPageResults(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenSearchTermIsApplied(): void
    {
        $request = new Request();
        $request->attributes->set(
            RedirectActionTypeSearchPageKernelListener::WEBSITE_SEARCH_TERM,
            new SearchTermStub()
        );
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenSearchTermSkipFlag(): void
    {
        $request = new Request();
        $request->query->set(BasicRedirectActionHandler::SKIP_SEARCH_TERM, '1');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenFeatureNotEnabled(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenNoSearchPhrase(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenNoSearchTerm(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(null);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenSearchTermActionNotRedirect(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $searchTerm = new SearchTermStub();
        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn($searchTerm);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->listener->onKernelEvent($event);
    }

    public function testWhenSearchTermActionRedirect(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect');
        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn($searchTerm);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new SearchTermRedirectActionEvent(Product::class, $searchTerm, $event));

        $this->listener->onKernelEvent($event);
    }
}
