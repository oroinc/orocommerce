<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WebsiteSearchController extends Controller
{
    /**
     * @Route("/", name="oro_website_search_results")
     *
     * @param Request $request
     * @return array
     */
    public function searchResultsAction(Request $request)
    {
        $searchString = trim($request->get('search'));

        // @todo It is just a simple temporary implementation of search . Proper one should be implemented in BB-5220
        $urlParams = [];
        if ($searchString) {
            $urlParams['frontend-product-search-grid'] = [
                '_filter' => ['all_text' => ['value' => $searchString, 'type' => 1]]
            ];
        }

        return $this->redirectToRoute('oro_product_frontend_product_index', $urlParams);
    }
}
