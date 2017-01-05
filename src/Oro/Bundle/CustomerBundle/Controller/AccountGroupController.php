<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupType;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountGroupHandler;

class AccountGroupController extends Controller
{
    /**
     * @Route("/", name="oro_customer_account_group_index")
     * @Template
     * @AclAncestor("oro_customer_account_group_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_customer.entity.account_group.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_customer_account_group_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_customer_account_group_view",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerGroup",
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
     * @Route("/create", name="oro_customer_account_group_create")
     * @Template("OroCustomerBundle:AccountGroup:update.html.twig")
     * @Acl(
     *      id="oro_customer_account_group_create",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerGroup",
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
     * @Route("/update/{id}", name="oro_customer_account_group_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_customer_account_group_update",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerGroup",
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
        $form = $this->createForm(AccountGroupType::NAME, $group);
        $handler = new AccountGroupHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($group)),
            $this->get('event_dispatcher')
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $group,
            $form,
            function (CustomerGroup $group) {
                return [
                    'route' => 'oro_customer_account_group_update',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            function (CustomerGroup $group) {
                return [
                    'route' => 'oro_customer_account_group_view',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.accountgroup.saved.message'),
            $handler
        );
    }

    /**
     * @Route("/info/{id}", name="oro_customer_account_group_info", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:AccountGroup/widget:info.html.twig")
     * @AclAncestor("oro_customer_account_group_view")
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
