<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_frontend_account_user_role_index")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:index.html.twig")
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
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_account_frontend_account_user_role_view_action",
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
            'form' => $handler->createView(),
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_frontend_account_user_role_create")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:update.html.twig")
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
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:update.html.twig")
     * @param AccountUserRole $role
     * @param Request $request
     * @return array
     */
    public function updateAction(AccountUserRole $role, Request $request)
    {
        $securityFacade = $this->get('oro_security.security_facade');

        if ($role->isPredefined()) {
            if ($request->isMethod(Request::METHOD_GET)) {
                $this->addFlash(
                    'warning',
                    $this->get('translator')
                        ->trans('orob2b.account.accountuserrole.frontend.edit-predifined-role.message')
                );
            }
            $isGranted = $securityFacade->isGranted('orob2b_account_frontend_account_user_role_create');
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
        $form = $handler->createForm($role);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
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
            $handler,
            function (AccountUserRole $entity, FormInterface $form, Request $request) use ($role) {
                return [
                    'form' => $form->createView(),
                    'entity' => $entity,
                    'isWidgetContext' => (bool)$request->get('_wid', false),
                    'predefined_role_id' => $role->getId(),
                ];
            }
        );
    }
}
