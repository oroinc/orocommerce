<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\Common\Util\ClassUtils;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserRoleHandler;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_customer_account_user_role_index")
     * @Template
     * @Acl(
     *      id="orob2b_customer_account_user_role_view",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUserRole",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_customer.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_customer_account_user_role_create")
     * @Template("OroB2BCustomerBundle:AccountUserRole:update.html.twig")
     * @Acl(
     *      id="orob2b_customer_account_user_role_create",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUserRole",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $roleClass = $this->container->getParameter('orob2b_customer.entity.account_user_role.class');

        return $this->update(new $roleClass());
    }

    /**
     * @Route("/update/{id}", name="orob2b_customer_account_user_role_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_account_user_role_update",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUserRole",
     *      permission="EDIT"
     * )
     *
     * @param AccountUserRole $role
     * @return array
     */
    public function updateAction(AccountUserRole $role)
    {
        return $this->update($role);
    }

    /**
     * @param AccountUserRole $role
     * @return array|RedirectResponse
     */
    protected function update(AccountUserRole $role)
    {
        $handler = $this->get('orob2b_customer.form.handler.account_user_role');
        $form = $handler->createForm($role);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $role,
            $form,
            function (AccountUserRole $role) {
                return [
                    'route' => 'orob2b_customer_account_user_role_update',
                    'parameters' => ['id' => $role->getId()]
                ];
            },
            function () {
                return [
                    'route' => 'orob2b_customer_account_user_role_index',
                ];
            },
            $this->get('translator')->trans('orob2b.customer.controller.accountuserrole.saved.message'),
            $handler
        );
    }
}
