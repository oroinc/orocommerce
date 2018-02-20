<?php

namespace Oro\Bundle\WebsiteSearchBundle\Controller\Frontend;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class WebsiteSearchController extends Controller
{
    /**
     * @Route("/", name="oro_website_search_results")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function searchResultsAction(Request $request)
    {
        $searchString = trim($request->get('search'));

        // @todo It is just a simple temporary implementation of search . Proper one should be implemented in BB-5220
        $urlParams = [];
        if ($searchString) {
            $urlParams['grid']['frontend-product-search-grid'] = http_build_query([
                AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                    'all_text' => ['value' => $searchString, 'type' => TextFilterType::TYPE_CONTAINS]
                ],
            ]);
        }

        return $this->redirectToRoute('oro_product_frontend_product_index', $urlParams);
    }
}
