<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_account_user_role_frontend_index")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:index.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_index",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_account_user_role_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_view_action",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="FRONTEND_ACCOUNT_ROLE_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param AccountUserRole $role
     *
     * @return array
     */
    public function viewAction(AccountUserRole $role)
    {
        $handler = $this->get('orob2b_account.form.handler.view_account_user_role');
        $handler->createForm($role);
        $handler->process($role);

        return [
            'entity' => $role,
            'form'   => $handler->createView()
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_account_account_user_role_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_account_account_user_role_frontend_view_action")
     * @param AccountUserRole $role
     * @return array
     */
    public function infoAction(AccountUserRole $role)
    {
        return [
            'entity' => $role
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
        $accountUserRole = $this->createNewRole();

        return $this->update($accountUserRole);
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_account_user_role_frontend_update", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:update.html.twig")
     * @param AccountUserRole $role
     * @return array
     */
    public function updateAction(AccountUserRole $role)
    {
        $securityFacade = $this->get('oro_security.security_facade');

        if ($role->isPredefined()) {
            if ($this->get('request')->isMethod(Request::METHOD_GET)) {
                $this->addFlash(
                    'warning',
                    $this->get('translator')
                        ->trans('orob2b.account.accountuserrole.frontend.edit-predifined-role.message')
                );
            }
            $isGranted = $securityFacade->isGranted('orob2b_account_account_user_role_frontend_create');
        } else {
            $isGranted = $securityFacade->isGranted('FRONTEND_ACCOUNT_ROLE_UPDATE', $role);
        }

        if (!$isGranted) {
            throw $this->createAccessDeniedException();
        }

        return $this->update($role);
    }

    /**
     * @param AccountUserRole $role
     * @return array|RedirectResponse
     */
    protected function update(AccountUserRole $role)
    {
        $handler = $this->get('orob2b_account.form.handler.update_account_user_role_frontend');
        if ($role->isPredefined()) {
            $newRole = $this->createNewRole($role);
        } else {
            $newRole = $role;
        }
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

    /**
     * @param AccountUserRole $role
     * @return AccountUserRole
     */
    protected function createNewRole(AccountUserRole $role = null)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getUser();

        if ($role) {
            $newRole = clone $role;
        } else {
            $accountUserClass = $this->container->getParameter('orob2b_account.entity.account_user_role.class');
            $newRole = new $accountUserClass();
        }

        $newRole->setAccount($accountUser->getAccount())
            ->setOrganization($accountUser->getOrganization());

        return $newRole;
    }
}
