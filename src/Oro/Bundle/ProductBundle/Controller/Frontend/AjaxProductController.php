<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Implements the following AJAX actions:
 * * get product names by SKUs
 * * get product images by product ID
 */
class AjaxProductController extends AbstractController
{
    /**
     * @Route(
     *      "/names-by-skus",
     *      name="oro_product_frontend_ajax_names_by_skus",
     *      methods={"POST"}
     * )
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
        $searchQuery->addWhere(
            Criteria::expr()->neq('type', Product::TYPE_CONFIGURABLE)
        );

        $searchQuery->setMaxResults(1000);

        $products = $searchQuery->getResult();

        $names = $this->prepareNamesData($products);

        return new JsonResponse($names);
    }

    /**
     * @Route(
     *      "/images-by-id/{id}",
     *      name="oro_product_frontend_ajax_images_by_id",
     *      requirements={"id"="\d+"},
     *      methods={"GET"}
     * )
     * @AclAncestor("oro_product_frontend_view")
     *
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     */
    public function productImagesByIdAction(Request $request, $id)
    {
        $productImagesURLsProvider = $this->get('oro_product.provider.product_images_urls');
        $filtersNames = $this->getFiltersNames($request);
        $images = $productImagesURLsProvider->getFilteredImagesByProductId($id, $filtersNames);

        return new JsonResponse($images);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getFiltersNames(Request $request)
    {
        return (array)$request->get('filters');
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
