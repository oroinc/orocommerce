<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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

    /**
     * @Route("/edit-inventory-status", name="oro_product_ajax_edit_inventory_status")
     * @Method("PATCH")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productEditInventoryStatus(Request $request)
    {
        $id = $request->get('id');
        $inventoryStatusId = $request->get('inventoryStatusId');

        if ($id === null || $inventoryStatusId === null) {
            return new JsonResponse(['code' => 400], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
        if (!$product) {
            return new JsonResponse(['code' => 400], Response::HTTP_BAD_REQUEST);
        }

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getDoctrine()->getRepository($inventoryStatusClassName)->find($inventoryStatusId);
        if (!$inventoryStatus) {
            return new JsonResponse(['code' => 400], Response::HTTP_BAD_REQUEST);
        }

        $product->setInventoryStatus($inventoryStatus);
        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return new JsonResponse();
    }
}
