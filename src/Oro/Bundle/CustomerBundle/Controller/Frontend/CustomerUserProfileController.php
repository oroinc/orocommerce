<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CustomerBundle\Form\Handler\FrontendCustomerUserHandler;

class CustomerUserProfileController extends Controller
{
    /**
     * @Route("/", name="oro_customer_frontend_customer_user_profile")
     * @Layout
     * @AclAncestor("oro_customer_frontend_customer_user_view")
     *
     * @return array
     */
    public function profileAction()
    {
        return [
            'data' => [
                'entity' => $this->getUser()
            ]
        ];
    }

    /**
     * Edit customer user form
     *
     * @Route("/update", name="oro_customer_frontend_customer_user_profile_update")
     * @Layout()
     * @AclAncestor("oro_customer_frontend_customer_user_update")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $customerUser = $this->getUser();
        $form = $this->get('oro_customer.provider.frontend_customer_user_form')
            ->getProfileForm($customerUser);
        $handler = new FrontendCustomerUserHandler(
            $form,
            $request,
            $this->get('oro_customer_user.manager')
        );
        $resultHandler = $this->get('oro_form.model.update_handler')->handleUpdate(
            $customerUser,
            $form,
            ['route' => 'oro_customer_frontend_customer_user_profile_update'],
            ['route' => 'oro_customer_frontend_customer_user_profile'],
            $this->get('translator')->trans('oro.customer.controller.customeruser.profile_updated.message'),
            $handler
        );

        if ($resultHandler instanceof Response) {
            return $resultHandler;
        }

        return [
            'data' => [
                'entity' => $customerUser
            ]
        ];
    }
}
