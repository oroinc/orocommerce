<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SearchBundle\Query\Result\Item;

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

        $products = $this->get('oro_product.search.repository.product')->searchFilteredBySkus($skus);
        $names    = $this->prepareNamesData($products);

        return new JsonResponse($names);
    }

    /**
     * @param array $products
     * @return array
     */
    private function prepareNamesData(array $products)
    {
        $names = [];
        /**
         * @var Item $product
         */
        foreach ($products as $product) {
            $selectedData                = $product->getSelectedData();
            $localeId                    = $this->get('oro_frontend_localization.manager.user_localization')
                ->getCurrentLocalization()
                ->getId();
            $nameKey                     = 'title_' . $localeId;
            $names[$selectedData['sku']] = [
                'name' => $selectedData[$nameKey],
            ];
        }

        return $names;
    }
}
