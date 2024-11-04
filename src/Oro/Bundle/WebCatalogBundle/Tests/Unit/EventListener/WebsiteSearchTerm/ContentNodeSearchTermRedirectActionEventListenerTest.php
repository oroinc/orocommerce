<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm\ContentNodeSearchTermRedirectActionEventListener;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\ContentNodeStub;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ContentNodeSearchTermRedirectActionEventListenerTest extends TestCase
{
    private ContentNodeTreeResolverInterface|MockObject $contentNodeTreeResolver;

    private RequestWebContentScopeProvider|MockObject $requestWebContentScopeProvider;

    private LocalizationHelper|MockObject $localizationHelper;

    private BasicRedirectActionHandler|MockObject $basicRedirectActionHandler;

    private ContentNodeSearchTermRedirectActionEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->basicRedirectActionHandler = $this->createMock(BasicRedirectActionHandler::class);
        $this->listener = new ContentNodeSearchTermRedirectActionEventListener(
            $this->contentNodeTreeResolver,
            $this->requestWebContentScopeProvider,
            $this->localizationHelper,
            $this->basicRedirectActionHandler
        );
    }

    public function testWhenActionTypeNotRedirect(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setRedirectActionType('content_node');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenRedirectActionTypeNotContentNode(): void
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

    public function testWhenNoRedirectContentNode(): void
    {
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node');

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenNoResolvedContentNode(): void
    {
        $redirectContentNode = new ContentNodeStub(42);
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node')
            ->setRedirectContentNode($redirectContentNode);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($redirectContentNode, $scopes, ['tree_depth' => 0])
            ->willReturn(null);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenNoContentNodeUrl(): void
    {
        $redirectContentNode = new ContentNodeStub(42);
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node')
            ->setRedirectContentNode($redirectContentNode);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($redirectContentNode, $scopes, ['tree_depth' => 0])
            ->willReturn($resolvedContentNode);

        $resolvedContentVariant = (new ResolvedContentVariant())
            ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url'));
        $resolvedContentNode
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn(null);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $requestEvent
            ->expects(self::never())
            ->method('setResponse');

        $this->listener->onRedirectAction($event);
    }

    public function testWhenHasContentNodeUrl(): void
    {
        $redirectContentNode = new ContentNodeStub(42);
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node')
            ->setRedirectContentNode($redirectContentNode);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($redirectContentNode, $scopes, ['tree_depth' => 0])
            ->willReturn($resolvedContentNode);

        $contentNodeUrl = '/sample/url';
        $resolvedContentVariant = (new ResolvedContentVariant())
            ->addLocalizedUrl((new LocalizedFallbackValue())->setString($contentNodeUrl));
        $resolvedContentNode
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn($contentNodeUrl);

        $requestEvent = $this->createMock(RequestEvent::class);
        $event = new SearchTermRedirectActionEvent(Product::class, $searchTerm, $requestEvent);

        $request = new Request();
        $request->query->set('search', 'sample_phrase');
        $requestEvent
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $response = new Response('content node content');
        $this->basicRedirectActionHandler
            ->expects(self::once())
            ->method('getResponse')
            ->with(
                $request,
                $searchTerm,
                $contentNodeUrl,
                [
                    'search' => 'sample_phrase',
                    'frontend-product-search-grid' => [
                        SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                    ],
                ]
            )
            ->willReturn($response);

        $requestEvent
            ->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->listener->onRedirectAction($event);
    }
}
