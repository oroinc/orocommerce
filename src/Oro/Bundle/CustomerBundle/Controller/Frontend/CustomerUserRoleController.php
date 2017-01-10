<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerUserRoleController extends Controller
{
    /**
     * @Route("/", name="oro_customer_frontend_customer_user_role_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="oro_account_frontend_customer_user_role_index",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUserRole",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_customer.entity.customer_user_role.class'),
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_customer_frontend_customer_user_role_view", requirements={"id"="\d+"})
     * @Layout()
     *
     * @param CustomerUserRole $role
     * @return array
     */
    public function viewAction(CustomerUserRole $role)
    {
        $isGranted = $role->isPredefined()
            ? $this->getSecurityFacade()->isGranted('oro_account_frontend_customer_user_role_view')
            : $this->getSecurityFacade()->isGranted('FRONTEND_ACCOUNT_ROLE_VIEW', $role);

        if (!$isGranted || !$role->isSelfManaged() || !$role->isPublic()) {
            throw $this->createAccessDeniedException();
        }

        return [
            'data' => [
                'entity' => $role
            ]
        ];
    }

    /**
     * @Route("/create", name="oro_customer_frontend_customer_user_role_create")
     * @Layout()
     * @Acl(
     *      id="oro_account_frontend_customer_user_role_create",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUserRole",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new CustomerUserRole());
    }

    /**
     * @Route("/update/{id}", name="oro_customer_frontend_customer_user_role_update", requirements={"id"="\d+"})
     * @Layout()
     *
     * @param CustomerUserRole $role
     * @param Request $request
     * @return array
     */
    public function updateAction(CustomerUserRole $role, Request $request)
    {
        $isGranted = $role->isPredefined()
            ? $this->getSecurityFacade()->isGranted('oro_account_frontend_customer_user_role_create')
            : $this->getSecurityFacade()->isGranted('FRONTEND_ACCOUNT_ROLE_UPDATE', $role);

        if (!$isGranted || !$role->isSelfManaged() || !$role->isPublic()) {
            throw $this->createAccessDeniedException();
        }

        if ($role->isPredefined() && $request->isMethod(Request::METHOD_GET)) {
            $this->addFlash(
                'warning',
                $this->get('translator')
                    ->trans('oro.customer.customeruserrole.frontend.edit-predifined-role.message')
            );
        }

        return $this->update($role);
    }

    /**
     * @param CustomerUserRole $role
     * @return array|RedirectResponse
     */
    protected function update(CustomerUserRole $role)
    {
        $handler = $this->get('oro_customer.form.handler.update_customer_user_role_frontend');
        $form = $handler->createForm($role);

        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            function (CustomerUserRole $role) {
                return [
                    'route' => 'oro_customer_frontend_customer_user_role_update',
                    'parameters' => ['id' => $role->getId()],
                ];
            },
            function (CustomerUserRole $role) {
                return [
                    'route' => 'oro_customer_frontend_customer_user_role_view',
                    'parameters' => ['id' => $role->getId()],
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.customeruserrole.saved.message'),
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
