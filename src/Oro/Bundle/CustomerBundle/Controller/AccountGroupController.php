<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupType;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountGroupHandler;

class AccountGroupController extends Controller
{
    /**
     * @Route("/", name="oro_account_group_index")
     * @Template
     * @AclAncestor("oro_account_group_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_account.entity.account_group.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_account_group_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_account_group_view",
     *      type="entity",
     *      class="OroCustomerBundle:AccountGroup",
     *      permission="VIEW"
     * )
     * @Template()
     *
     * @param AccountGroup $group
     * @return array
     */
    public function viewAction(AccountGroup $group)
    {
        return [
            'entity' => $group
        ];
    }

    /**
     * @Route("/create", name="oro_account_group_create")
     * @Template("OroCustomerBundle:AccountGroup:update.html.twig")
     * @Acl(
     *      id="oro_account_group_create",
     *      type="entity",
     *      class="OroCustomerBundle:AccountGroup",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new AccountGroup());
    }

    /**
     * @Route("/update/{id}", name="oro_account_group_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_account_group_update",
     *      type="entity",
     *      class="OroCustomerBundle:AccountGroup",
     *      permission="EDIT"
     * )
     *
     * @param AccountGroup $group
     * @return array
     */
    public function updateAction(AccountGroup $group)
    {
        return $this->update($group);
    }

    /**
     * @param AccountGroup $group
     * @return array|RedirectResponse
     */
    protected function update(AccountGroup $group)
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
            function (AccountGroup $group) {
                return [
                    'route' => 'oro_account_group_update',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            function (AccountGroup $group) {
                return [
                    'route' => 'oro_account_group_view',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.accountgroup.saved.message'),
            $handler
        );
    }

    /**
     * @Route("/info/{id}", name="oro_account_group_info", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:AccountGroup/widget:info.html.twig")
     * @AclAncestor("oro_account_group_view")
     *
     * @param AccountGroup $group
     * @return array
     */
    public function infoAction(AccountGroup $group)
    {
        return [
            'entity' => $group
        ];
    }
}
