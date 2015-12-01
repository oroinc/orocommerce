<?php

namespace OroB2B\Bundle\WarehouseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;
use OroB2B\Bundle\WarehouseBundle\Form\Handler\WarehouseInventoryLevelHandler;

class WarehouseInventoryLevelController extends Controller
{
    /**
     * Edit product warehouse inventory levels
     *
     * @Route("/update/{id}", name="orob2b_warehouse_inventory_level_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_warehouse_inventory_update",
     *      type="entity",
     *      class="OroB2BWarehouseBundle:WarehouseInventoryLevel",
     *      permission="EDIT"
     * )
     *
     * @param Product $product
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Product $product, Request $request)
    {
        $form = $this->createForm(
            WarehouseInventoryLevelGridType::NAME,
            null,
            ['product' => $product]
        );

        $handler = new WarehouseInventoryLevelHandler(
            $form,
            $this->getDoctrine()->getManagerForClass('OroB2BWarehouseBundle:WarehouseInventoryLevel'),
            $request,
            $this->get('orob2b_product.service.quantity_rounding')
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $product,
            $form,
            null,
            null,
            null,
            $handler
        );
    }
}
