<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_customer_frontend_account_user_view", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="oro_account_frontend_account_user_view",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param CustomerUser $accountUser
     * @return array
     */
    public function viewAction(CustomerUser $accountUser)
    {
        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }

    /**
     * @Route("/", name="oro_customer_frontend_account_user_index")
     * @Layout(vars={"entity_class"})
     * @AclAncestor("oro_account_frontend_account_user_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_customer.entity.account_user.class')
        ];
    }

    /**
     * Create account user form
     *
     * @Route("/create", name="oro_customer_frontend_account_user_create")
     * @Layout
     * @Acl(
     *      id="oro_account_frontend_account_user_create",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new CustomerUser(), $request);
    }

    /**
     * Edit account user form
     *
     * @Route("/update/{id}", name="oro_customer_frontend_account_user_update", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="oro_account_frontend_account_user_update",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     * @param CustomerUser $accountUser
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(CustomerUser $accountUser, Request $request)
    {
        return  $this->update($accountUser, $request);
    }

    /**
     * @param CustomerUser $accountUser
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(CustomerUser $accountUser, Request $request)
    {
        $form = $this->get('oro_customer.provider.frontend_account_user_form')
            ->getAccountUserForm($accountUser);
        $handler = new AccountUserHandler(
            $form,
            $request,
            $this->get('oro_account_user.manager'),
            $this->get('oro_security.security_facade'),
            $this->get('translator'),
            $this->get('logger')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            function (CustomerUser $accountUser) {
                return [
                    'route' => 'oro_customer_frontend_account_user_update',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            function (CustomerUser $accountUser) {
                return [
                    'route' => 'oro_customer_frontend_account_user_view',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.customeruser.saved.message'),
            $handler
        );

        if ($result instanceof Response) {
            return $result;
        }

        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }
}
