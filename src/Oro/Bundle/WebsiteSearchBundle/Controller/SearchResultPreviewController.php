<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle search term preview.
 */
class SearchResultPreviewController extends AbstractController
{
    /**
     * @Route("/search-term-preview/{id}", name="oro_website_search_term_preview")
     * @AclAncestor("oro_website_search_result_history_view")
     * @return RedirectResponse
     */
    public function previewAction(SearchResultHistory $historyEntry)
    {
        return new RedirectResponse(
            $this->get(WebsiteUrlResolver::class)
                ->getWebsitePath(
                    'oro_product_frontend_product_search',
                    [
                        'search' => $historyEntry->getSearchTerm()
                    ],
                    $historyEntry->getWebsite()
                )
        );
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                WebsiteUrlResolver::class
            ]
        );
    }
}
