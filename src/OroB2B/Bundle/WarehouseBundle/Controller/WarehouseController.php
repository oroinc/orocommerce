<?php

namespace OroB2B\Bundle\WarehouseBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseType;

class WarehouseController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_warehouse_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_warehouse_view",
     *      type="entity",
     *      class="OroB2BWarehouseBundle:Warehouse",
     *      permission="VIEW"
     * )
     *
     * @param Warehouse $warehouse
     *
     * @return array
     */
    public function viewAction(Warehouse $warehouse)
    {
        return [
            'entity' => $warehouse,
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_warehouse_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_warehouse_view")
     *
     * @param Warehouse $warehouse
     *
     * @return array
     */
    public function infoAction(Warehouse $warehouse)
    {
        return [
            'warehouse' => $warehouse,
        ];
    }

    /**
     * @Route("/", name="orob2b_warehouse_index")
     * @Template
     * @AclAncestor("orob2b_warehouse_view")
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
     * Create warehouse
     *
     * @Route("/create", name="orob2b_warehouse_create")
     * @Template("OroB2BWarehouseBundle:Warehouse:update.html.twig")
     * @Acl(
     *      id="orob2b_warehouse_create",
     *      type="entity",
     *      class="OroB2BWarehouseBundle:Warehouse",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Warehouse());
    }

    /**
     * Edit warehouse form
     *
     * @Route("/update/{id}", name="orob2b_warehouse_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_warehouse_update",
     *      type="entity",
     *      class="OroB2BWarehouseBundle:Warehouse",
     *      permission="EDIT"
     * )
     *
     * @param Warehouse $warehouse
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Warehouse $warehouse)
    {
        return $this->update($warehouse);
    }

    /**
     * @param Warehouse $warehouse
     *
     * @return array|RedirectResponse
     */
    protected function update(Warehouse $warehouse)
    {
        $form = $this->createForm(WarehouseType::NAME, $warehouse);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $warehouse,
            $form,
            function (Warehouse $warehouse) {
                return [
                    'route' => 'orob2b_warehouse_update',
                    'parameters' => ['id' => $warehouse->getId()]
                ];
            },
            function (Warehouse $warehouse) {
                return [
                    'route' => 'orob2b_warehouse_view',
                    'parameters' => ['id' => $warehouse->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.warehouse.controller.warehouse.saved.message')
        );
    }
}
