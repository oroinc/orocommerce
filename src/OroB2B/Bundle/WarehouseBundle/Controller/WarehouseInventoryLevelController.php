<?php

namespace OroB2B\Bundle\WarehouseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;
use OroB2B\Bundle\WarehouseBundle\Form\Handler\WarehouseInventoryLevelHandler;

class WarehouseInventoryLevelController extends Controller
{
    /**
     * @Route("/", name="orob2b_warehouse_inventory_level_index")
     * @Template
     * @AclAncestor("orob2b_warehouse_inventory_level_index")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_warehouse.entity.warehouse.class'),
        ];
    }

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
        if (!$this->get('oro_security.security_facade')->isGranted('EDIT', $product)) {
            throw new AccessDeniedHttpException();
        }

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

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $product,
            $form,
            null,
            null,
            null,
            $handler
        );

        if ($result instanceof Response) {
            return $result;
        }

        return array_merge($result, $this->widgetNoDataReasonsCheck($product));
    }

    /**
     * @param Product $product
     * @return array
     */
    private function widgetNoDataReasonsCheck(Product $product)
    {
        $noDataReason = '';
        if (0 === count($product->getUnitPrecisions())) {
            $noDataReason = 'orob2b.warehouse.warehouseinventorylevel.error.units';
        } elseif (0 === count($this->getAvailableWarehouses())) {
            $noDataReason = 'orob2b.warehouse.warehouseinventorylevel.error.warehouses';
        }

        return $noDataReason
            ? ['noDataReason' => $this->get('translator')->trans($noDataReason)]
            : [];
    }

    /**
     * @return array|Warehouse[]
     */
    private function getAvailableWarehouses()
    {
        $warehouseClass = $this->getParameter('orob2b_warehouse.entity.warehouse.class');

        return $this->getDoctrine()
            ->getManagerForClass($warehouseClass)
            ->getRepository($warehouseClass)
            ->findAll();
    }
}
