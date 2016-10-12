<?php

namespace Oro\Bundle\InventoryBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTypeExtension;
use Oro\Bundle\InventoryBundle\Form\Handler\InventoryLevelHandler;
use Oro\Bundle\InventoryBundle\Form\Type\InventoryLevelGridType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class InventoryLevelController extends Controller
{
    /**
     * @Route("/", name="oro_inventory_level_index")
     * @Template
     * @AclAncestor("oro_inventory_level_index")
     *
     * @return array
     */
    public function indexAction()
    {
        $entityName = $this->container->getParameter('oro_inventory.entity.inventory_level.class');

        return [
            'entity_class' => $entityName,
            'exportProcessors' => array_keys(InventoryLevelExportTypeExtension::getProcessorAliases()),
            'exportTemplateProcessors' => array_keys(
                InventoryLevelExportTemplateTypeExtension::getProcessorAliases()
            ),
        ];
    }

    /**
     * Edit product inventory levels
     *
     * @Route("/update/{id}", name="oro_inventory_level_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_product_inventory_update",
     *      type="entity",
     *      class="OroInventoryBundle:InventoryLevel",
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
            InventoryLevelGridType::NAME,
            null,
            ['product' => $product]
        );

        $handler = new InventoryLevelHandler(
            $form,
            $this->getDoctrine()->getManagerForClass('OroInventoryBundle:InventoryLevel'),
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
            $noDataReason = 'oro.inventory.inventorylevel.error.units';
        }

        return $noDataReason
            ? ['noDataReason' => $this->get('translator')->trans($noDataReason)]
            : [];
    }
}
