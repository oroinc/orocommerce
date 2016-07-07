<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_frontend_account_user_role_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_account_frontend_account_user_role_index",
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
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class'),
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_frontend_account_user_role_view", requirements={"id"="\d+"})
     * @Layout()
     *
     * @param AccountUserRole $role
     *
     * @return array
     */
    public function viewAction(AccountUserRole $role)
    {
        $isGranted = $role->isPredefined()
            ? $this->getSecurityFacade()->isGranted('orob2b_account_frontend_account_user_role_view')
            : $this->getSecurityFacade()->isGranted('FRONTEND_ACCOUNT_ROLE_VIEW', $role);

        if (!$isGranted) {
            throw $this->createAccessDeniedException();
        }

        return [
            'data' => [
                'entity' => $role
            ]
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_frontend_account_user_role_create")
     * @Layout()
     * @Acl(
     *      id="orob2b_account_frontend_account_user_role_create",
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
        return $this->update(new AccountUserRole());
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_frontend_account_user_role_update", requirements={"id"="\d+"})
     * @Layout()
     * @param AccountUserRole $role
     * @param Request $request
     * @return array
     */
    public function updateAction(AccountUserRole $role, Request $request)
    {
        $isGranted = $role->isPredefined()
            ? $this->getSecurityFacade()->isGranted('orob2b_account_frontend_account_user_role_create')
            : $this->getSecurityFacade()->isGranted('FRONTEND_ACCOUNT_ROLE_UPDATE', $role);
        
        if (!$isGranted) {
            throw $this->createAccessDeniedException();
        }

        if ($role->isPredefined() && $request->isMethod(Request::METHOD_GET)) {
            $this->addFlash(
                'warning',
                $this->get('translator')
                    ->trans('orob2b.account.accountuserrole.frontend.edit-predifined-role.message')
            );
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
        $form = $handler->createForm($role);

        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            function (AccountUserRole $role) {
                return [
                    'route' => 'orob2b_account_frontend_account_user_role_update',
                    'parameters' => ['id' => $role->getId()],
                ];
            },
            function (AccountUserRole $role) {
                return [
                    'route' => 'orob2b_account_frontend_account_user_role_view',
                    'parameters' => ['id' => $role->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountuserrole.saved.message'),
            $handler
        );

        if ($response instanceof Response) {
            return $response;
        }

        return [
            'data' => [
                'entity' => $role
            ]
        ];
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }
}
