<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle requests related to search results manipulation.
 */
class SearchResultController extends AbstractController
{
    /**
     * @Route("/result-history", name="oro_website_search_search_history")
     * @Template
     * @Acl(
     *      id="oro_website_search_search_history",
     *      type="entity",
     *      class="OroWebsiteSearchBundle:SearchResult",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'gridName' => 'website_search_results_grid'
        ];
    }
}
