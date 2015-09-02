<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_account_user_role_frontend_index")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:index.html.twig")
     * @AclAncestor("orob2b_account_account_user_role_frontend_view")
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_count_account_user_role_frontend_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param AccountUserRole $accountUserRole
     *
     * @return array
     */
    public function viewAction(AccountUserRole $accountUserRole)
    {
        // TODO: Add template for view page
        return [
            'entity' => $accountUserRole
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_account_user_role_frontend_create")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $accountUserRoleClass = $this->container->getParameter('orob2b_account.entity.account_user_role.class');

        /** @var AccountUserRole $accountUserRole */
        $accountUserRole = new $accountUserRoleClass();

        /** @var AccountUser $accountUser */
        $accountUser = $this->getUser();
        $accountUserRole->setAccount($accountUser->getAccount())
            ->setOrganization($accountUser->getOrganization());

        return $this->update($accountUserRole);
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_account_user_role_frontend_update", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="FRONTEND_ACCOUNT_ROLE_UPDATE",
     *      group_name="commerce"
     * )
     * @param AccountUserRole $role
     * @return array
     */
    public function updateAction(AccountUserRole $role)
    {
        if ($role->isPredefined()) {
            $newRole = clone $role;
            /** @var AccountUser $accountUser */
            $accountUser = $this->getUser();
            $newRole->setAccount($accountUser->getAccount())
                ->setOrganization($accountUser->getOrganization());
        } else {
            $newRole = $role;
        }
        return $this->update($role, $newRole);
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUserRole $newRole
     * @return array|RedirectResponse
     */
    protected function update(AccountUserRole $role, AccountUserRole $newRole)
    {
        $handler = $this->get('orob2b_account.form.handler.account_user_role_frontend');

        $form = $handler->createForm($newRole);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $role,
            $form,
            function (AccountUserRole $role) {
                return [
                    'route' => 'orob2b_account_account_user_role_frontend_update',
                    'parameters' => ['id' => $role->getId()]
                ];
            },
            function () {
                return [
                    'route' => 'orob2b_account_account_user_role_frontend_index',
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountuserrole.saved.message'),
            $handler
        );

    }

}
