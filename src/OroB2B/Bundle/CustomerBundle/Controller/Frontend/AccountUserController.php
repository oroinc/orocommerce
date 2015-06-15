<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class AccountUserController extends Controller
{
    /**
     * @Route("/profile", name="orob2b_customer_frontend_account_user_profile")
     * @Template("OroB2BCustomerBundle:AccountUser:view.html.twig")
     * @Acl(
     *      id="orob2b_customer_account_user_view",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function profileAction()
    {
        return [
            'entity' => $this->getUser(),
            'editRoute' => 'orob2b_customer_frontend_account_user_update'
        ];
    }

    /**
     * Edit account user form
     *
     * @Route("/profile/update", name="orob2b_customer_frontend_account_user_update")
     * @Template
     * @Acl(
     *      id="orob2b_customer_account_user_update",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="EDIT"
     * )
     * @return array|RedirectResponse
     */
    public function updateAction()
    {
        return $this->update($this->getUser());
    }
}
