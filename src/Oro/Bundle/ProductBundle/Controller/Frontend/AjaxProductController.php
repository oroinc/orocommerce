<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class AjaxProductController extends Controller
{
    /**
     * @Route(
     *      "/names-by-skus",
     *      name="orob2b_product_frontend_ajax_names_by_skus"
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productNamesBySkusAction(Request $request)
    {
        $names = [];
        $skus = (array)$request->request->get('skus');

        if (0 === count($skus)) {
            return new JsonResponse($names);
        }

        $queryBuilder = $this->getProductRepository()->getFilterProductWithNamesQueryBuilder($skus);
        /** @var Product[] $products */
        $products = $this->container->get('orob2b_product.product.manager')
            ->restrictQueryBuilder($queryBuilder, [])->getQuery()->getResult();

        foreach ($products as $product) {
            $names[$product->getSku()] = [
                'name' => (string)$product->getDefaultName(),
            ];
        }

        return new JsonResponse($names);
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        $productClass = $this->container->getParameter('orob2b_product.entity.product.class');

        return $this->getDoctrine()->getManagerForClass($productClass)->getRepository($productClass);
    }
}
