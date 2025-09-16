<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handle requests related to search results manipulation.
 */
class SearchResultHistoryController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/result-history', name: 'oro_website_search_result_history_index')]
    #[Template('@OroWebsiteSearch/SearchResultHistory/index.html.twig')]
    #[Acl(
        id: 'oro_website_search_result_history_view',
        type: 'entity',
        class: SearchResultHistory::class,
        permission: 'VIEW'
    )]
    public function indexAction()
    {
        return [
            'entity_class' => SearchResultHistory::class,
        ];
    }
}
