<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_account_user_role_index")
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_role_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_account_user_role_create")
     * @Template("OroB2BAccountBundle:AccountUserRole:update.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $roleClass = $this->container->getParameter('orob2b_account.entity.account_user_role.class');

        return $this->update(new $roleClass());
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_account_user_role_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_role_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
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
        $handler = $this->get('orob2b_account.form.handler.account_user_role');
        $form = $handler->createForm($role);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $role,
            $form,
            function (AccountUserRole $role) {
                return [
                    'route' => 'orob2b_account_account_user_role_update',
                    'parameters' => ['id' => $role->getId()]
                ];
            },
            function () {
                return [
                    'route' => 'orob2b_account_account_user_role_index',
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountuserrole.saved.message'),
            $handler
        );
    }
}
