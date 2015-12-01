<?php

namespace OroB2B\Bundle\WarehouseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;
use OroB2B\Bundle\WarehouseBundle\Form\Handler\WarehouseInventoryLevelGridHandler;

class WarehouseInventoryLevelController extends Controller
{
    /**
     * Edit product warehouse inventory levels
     *
     * @Route("/view/{id}", name="orob2b_warehouse_inventory_level_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_warehouse_inventory_level_update")
     *
     * @param Product $product
     * @return array
     */
    public function viewAction(Product $product)
    {
        $form = $this->createForm(
            WarehouseInventoryLevelGridType::NAME,
            null,
            [
                'product_id' => $product->getId(),
                'action' => $this->generateUrl('orob2b_warehouse_inventory_level_update', ['id' => $product->getId()]),
                'method' => 'POST'
            ]
        );

        return ['form' => $form->createView()];
    }

    /**
     * Edit product warehouse inventory levels
     *
     * @Route("/update/{id}", name="orob2b_warehouse_inventory_level_update", requirements={"id"="\d+"})
     * @Template("OroB2BWarehouseBundle:WarehouseInventoryLevel:widget/view.html.twig")
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
        return $this->update($product, $request);
    }

    /**
     * @param Product $product
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(Product $product, Request $request)
    {
        $form = $this->createForm(
            WarehouseInventoryLevelGridType::NAME,
            null,
            ['product_id' => $product->getId()]
        );

        $handler = new WarehouseInventoryLevelGridHandler(
            $form,
            $this->getDoctrine()->getManager(),
            $request,
            $this->get('orob2b_product.service.rounding')
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
