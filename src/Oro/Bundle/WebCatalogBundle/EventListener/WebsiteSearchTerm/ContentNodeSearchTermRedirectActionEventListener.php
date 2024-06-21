<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;

/**
 * Forwards/redirects user to a content node specified in a {@see SearchTerm}.
 */
class ContentNodeSearchTermRedirectActionEventListener
{
    public function __construct(
        private readonly ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        private readonly RequestWebContentScopeProvider $requestWebContentScopeProvider,
        private readonly LocalizationHelper $localizationHelper,
        private readonly BasicRedirectActionHandler $basicRedirectActionHandler
    ) {
    }

    public function onRedirectAction(SearchTermRedirectActionEvent $event): void
    {
        $searchTerm = $event->getSearchTerm();
        if (!$this->supports($searchTerm)) {
            return;
        }

        $contentNode = $searchTerm->getRedirectContentNode();
        if (!$contentNode) {
            return;
        }

        $scopes = $this->requestWebContentScopeProvider->getScopes();
        $resolvedContentNode = $this->contentNodeTreeResolver
            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => 0]);
        if (!$resolvedContentNode) {
            return;
        }

        $resolvedContentVariant = $resolvedContentNode->getResolvedContentVariant();
        $redirectUrl = $this->localizationHelper->getLocalizedValue($resolvedContentVariant->getLocalizedUrls());
        if (!$redirectUrl) {
            return;
        }

        $requestEvent = $event->getRequestEvent();
        $request = $requestEvent->getRequest();

        $response = $this->basicRedirectActionHandler->getResponse(
            $request,
            $searchTerm,
            $redirectUrl,
            [
                'frontend-product-search-grid' => [
                    // Ensures that a content node will not be pre-filtered with the search phrase specified previously.
                    SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                ],
            ] + $request->query->all()
        );

        if ($response !== null) {
            $requestEvent->setResponse($response);
        }
    }

    private function supports(SearchTerm $searchTerm): bool
    {
        return $searchTerm->getActionType() === 'redirect'
            && $searchTerm->getRedirectActionType() === 'content_node';
    }
}
