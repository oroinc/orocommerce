<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxProductController extends Controller
{
    /**
     * @Route("/edit-name", name="oro_product_ajax_edit_name")
     * @Method("PATCH")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productEditName(Request $request)
    {
        $id = $request->get('id');
        $name = $request->get('productName');

        if ($id === null || $name === null) {
            return new JsonResponse(['code' => 400], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
        if (!$product) {
            return new JsonResponse(['code' => 400], Response::HTTP_BAD_REQUEST);
        }

        $product->setDefaultName($name);
        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return new JsonResponse();
    }
}
