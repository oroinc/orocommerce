<?php

namespace Oro\Bundle\WarehouseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;
use Oro\Bundle\WarehouseBundle\Form\Handler\WarehouseInventoryLevelHandler;
use Oro\Bundle\WarehouseBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Oro\Bundle\WarehouseBundle\Form\Extension\InventoryLevelExportTypeExtension;

class WarehouseInventoryLevelController extends Controller
{
    /**
     * @Route("/", name="oro_warehouse_inventory_level_index")
     * @Template
     * @AclAncestor("oro_warehouse_inventory_level_index")
     *
     * @return array
     */
    public function indexAction()
    {
        $entityName = $this->container->getParameter('oro_warehouse.entity.warehouse_inventory_level.class');

        return [
            'entity_class' => $entityName,
            'exportProcessors' => array_keys(InventoryLevelExportTypeExtension::getProcessorAliases()),
            'exportTemplateProcessors' => array_keys(
                InventoryLevelExportTemplateTypeExtension::getProcessorAliases()
            ),
        ];
    }

    /**
     * Edit product warehouse inventory levels
     *
     * @Route("/update/{id}", name="oro_warehouse_inventory_level_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_product_warehouse_inventory_update",
     *      type="entity",
     *      class="OroWarehouseBundle:WarehouseInventoryLevel",
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
            $this->getDoctrine()->getManagerForClass('OroWarehouseBundle:WarehouseInventoryLevel'),
            $request,
            $this->get('oro_product.service.quantity_rounding')
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
            $noDataReason = 'oro.warehouse.warehouseinventorylevel.error.units';
        }

        return $noDataReason
            ? ['noDataReason' => $this->get('translator')->trans($noDataReason)]
            : [];
    }
}
