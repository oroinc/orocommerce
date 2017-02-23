<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Result;

class AjaxProductController extends Controller
{
    /**
     * @Route(
     *      "/names-by-skus",
     *      name="oro_product_frontend_ajax_names_by_skus"
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productNamesBySkusAction(Request $request)
    {
        $names = [];
        $skus  = (array)$request->request->get('skus');

        if (0 === count($skus)) {
            return new JsonResponse($names);
        }

        $searchQuery = $this->get('oro_product.website_search.repository.product')->getFilterSkuQuery($skus);

        // Configurable products require additional option selection is not implemented yet
        // Thus we need to hide configurable products
        // @TODO remove after configurable products require additional option selection implementation
        $searchQuery->addWhere(
            Criteria::expr()->neq('type', Product::TYPE_CONFIGURABLE)
        );

        $products = $searchQuery->getResult();

        $names = $this->prepareNamesData($products);

        return new JsonResponse($names);
    }

    /**
     * @param Result $products
     * @return array
     */
    private function prepareNamesData(Result $products)
    {
        $names = [];

        foreach ($products as $product) {
            $selectedData                = $product->getSelectedData();
            $names[$selectedData['sku']] = [
                'name' => $selectedData['name'],
            ];
        }

        return $names;
    }
}
