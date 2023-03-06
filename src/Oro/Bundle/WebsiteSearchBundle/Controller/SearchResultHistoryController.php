<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle requests related to search results manipulation.
 */
class SearchResultHistoryController extends AbstractController
{
    /**
     * @Route("/result-history", name="oro_website_search_result_history_index")
     * @Template
     * @Acl(
     *      id="oro_website_search_result_history_view",
     *      type="entity",
     *      class="Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => SearchResultHistory::class,
        ];
    }
}
