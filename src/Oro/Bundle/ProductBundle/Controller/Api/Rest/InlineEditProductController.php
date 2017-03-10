<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("productinlineedit")
 * @NamePrefix("oro_api_")
 */
class InlineEditProductController extends FOSRestController
{
    /**
     * @Patch("inline-edit/product/{id}/name/patch")
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function patchEditNameAction(Request $request, Product $product)
    {
        $productName = $request->get('productName');

        if ($productName === null) {
            return parent::handleView($this->view([], Codes::HTTP_NOT_FOUND));
        }

        $product->setDefaultName($productName);
        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Codes::HTTP_OK));
    }

    /**
     * @Patch("inline-edit/product/{id}/inventory-status/patch")
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function patchEditInventoryStatusAction(Request $request, Product $product)
    {
        $inventoryStatusId = $request->get('inventoryStatusId');

        if ($inventoryStatusId === null) {
            return parent::handleView($this->view([], Codes::HTTP_BAD_REQUEST));
        }

        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getDoctrine()
            ->getRepository(ExtendHelper::buildEnumValueClassName('prod_inventory_status'))
            ->find($inventoryStatusId);

        if (!$inventoryStatus) {
            return parent::handleView($this->view([], Codes::HTTP_NOT_FOUND));
        }

        $product->setInventoryStatus($inventoryStatus);
        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Codes::HTTP_OK));
    }
}
