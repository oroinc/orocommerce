<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;
use OroB2B\Bundle\CustomerAdminBundle\Form\Type\CustomerGroupType;

class CustomerGroupController extends Controller
{
    /**
     * @Route("/", name="orob2b_customer_admin_group_index")
     * @Template
     * @AclAncestor("orob2b_customer_admin_group_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_customer_admin.entity.customer_group.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_group_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="orob2b_customer_admin_group_view",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:CustomerGroup",
     *      permission="VIEW"
     * )
     * @Template()
     *
     * @param CustomerGroup $group
     * @return array
     */
    public function viewAction(CustomerGroup $group)
    {
        return [
            'entity' => $group
        ];
    }

    /**
     * @Route("/create", name="orob2b_customer_admin_group_create")
     * @Template("OroB2BCustomerAdminBundle:CustomerGroup:update.html.twig")
     * @Acl(
     *      id="orob2b_customer_admin_group_create",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:CustomerGroup",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new CustomerGroup());
    }

    /**
     * @Route("/update/{id}", name="orob2b_customer_admin_group_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_admin_group_update",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:CustomerGroup",
     *      permission="EDIT"
     * )
     *
     * @param CustomerGroup $group
     * @return array
     */
    public function updateAction(CustomerGroup $group)
    {
        return $this->update($group);
    }

    /**
     * @param CustomerGroup $group
     * @return array|RedirectResponse
     */
    protected function update(CustomerGroup $group)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $group,
            $this->createForm(CustomerGroupType::NAME, $group),
            function (CustomerGroup $group) {
                return [
                    'route' => 'orob2b_customer_admin_group_update',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            function (CustomerGroup $group) {
                return [
                    'route' => 'orob2b_customer_admin_group_view',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.customeradmin.controller.customergroup.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_customer_admin_group_info", requirements={"id"="\d+"})
     * @Template("OroB2BCustomerAdminBundle:CustomerGroup/widget:info.html.twig")
     * @AclAncestor("orob2b_customer_admin_group_view")
     *
     * @param CustomerGroup $group
     * @return array
     */
    public function infoAction(CustomerGroup $group)
    {
        return [
            'entity' => $group
        ];
    }
}
