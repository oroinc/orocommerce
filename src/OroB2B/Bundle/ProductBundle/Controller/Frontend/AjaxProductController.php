<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

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
        $skus = (array)$request->request->get('skus');

        if (0 === count($skus)) {
            return new JsonResponse([]);
        }

        $productClass = $this->container->getParameter('orob2b_product.product.class');
        /** @var ProductRepository $repo */
        $repo = $this->getDoctrine()
            ->getManagerForClass($productClass)
            ->getRepository($productClass);

        $names = $repo->getProductNamesBySkus($skus);

        return new JsonResponse($names);
    }
}
