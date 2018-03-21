<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeChainProvider;
use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;

/**
 * Provide actions for frontend search
 *
 * @package Oro\Bundle\WebsiteSearchBundle\Controller\Frontend
 */
class WebsiteSearchController extends Controller
{
    /**
     * @Route("/", name="oro_website_search_results")
     *
     * @return RedirectResponse
     */
    public function searchResultsAction()
    {
        $queryStringProvider = $this->getQueryStringProvider();

        $searchString      = $queryStringProvider->getSearchQueryString();
        $searchType        = $queryStringProvider->getSearchQuerySearchType();
        $websiteSearchType = $this->getSearchTypeChainProvider()->getSearchTypeOrDefault($searchType);
        $route             = $websiteSearchType->getRoute($searchString);
        $routeParameters   = $websiteSearchType->getRouteParameters($searchString);

        return $this->redirectToRoute($route, $routeParameters);
    }

    /**
     * @return WebsiteSearchTypeChainProvider
     */
    private function getSearchTypeChainProvider(): WebsiteSearchTypeChainProvider
    {
        return $this->get('oro_website_search.search_type_chain_provider');
    }

    /**
     * @return QueryStringProvider
     */
    private function getQueryStringProvider(): QueryStringProvider
    {
        return $this->get('oro_website_search.query_string.query_string_provider');
    }
}
