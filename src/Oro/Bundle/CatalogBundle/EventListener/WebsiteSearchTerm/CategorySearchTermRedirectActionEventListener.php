<?php

namespace Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Forwards/redirects user to a category specified in a {@see SearchTerm}.
 */
class CategorySearchTermRedirectActionEventListener
{
    private bool $defaultIncludeSubcategories = true;

    public function __construct(
        private readonly BasicRedirectActionHandler $basicRedirectActionHandler,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function setDefaultIncludeSubcategories(bool $defaultIncludeSubcategories): void
    {
        $this->defaultIncludeSubcategories = $defaultIncludeSubcategories;
    }

    public function onRedirectAction(SearchTermRedirectActionEvent $event): void
    {
        $searchTerm = $event->getSearchTerm();
        if (!$this->supports($searchTerm)) {
            return;
        }

        $category = $searchTerm->getRedirectCategory();
        if (!$category) {
            return;
        }

        $requestEvent = $event->getRequestEvent();
        $request = $requestEvent->getRequest();
        $redirectUrl = $this->urlGenerator->generate('oro_product_frontend_product_index');

        $response = $this->basicRedirectActionHandler->getResponse(
            $request,
            $searchTerm,
            $redirectUrl,
            [
                'frontend-product-search-grid' => [
                    // Ensures that a content node will not be pre-filtered with the search phrase specified previously.
                    SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                ],
                'categoryId' => $category->getId(),
                'includeSubcategories' => $this->defaultIncludeSubcategories,
            ] + $request->query->all()
        );

        if ($response !== null) {
            $requestEvent->setResponse($response);
        }
    }

    private function supports(SearchTerm $searchTerm): bool
    {
        return $searchTerm->getActionType() === 'redirect'
            && $searchTerm->getRedirectActionType() === 'category';
    }
}
