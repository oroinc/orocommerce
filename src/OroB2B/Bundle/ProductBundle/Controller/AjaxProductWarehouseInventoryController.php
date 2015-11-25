<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class AjaxProductWarehouseInventoryController extends Controller
{
    /**
     * Edit product warehouse inventory levels
     *
     * @Route("/update/{id}", name="orob2b_product_warehouse_inventory_update_widget", requirements={"id"="\d+"})
     * @Template("OroB2BProductBundle:ProductWarehouse:widget/update.html.twig")
     * @Acl(
     *      id="orob2b_product_warehouse_inventory_update",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="EDIT"
     * )
     *
     * @param Product $product
     * @return mixed
     */
    public function updateAction(Product $product)
    {
        return [
            'entity' => $product
        ];
    }
}
