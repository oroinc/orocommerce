<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\CustomerBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * @Route("/profile", name="orob2b_customer_frontend_account_user_profile")
     * @Template
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
            'entity' => $this->getUser()
        ];
    }

    /**
     * Create account user form
     *
     * @Route("/register", name="orob2b_customer_frontend_account_user_register")
     * @Template("OroB2BCustomerBundle:AccountUser:register.html.twig")
     * @Acl(
     *      id="orob2b_customer_account_user_create",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function registerAction()
    {
        return $this->update(new AccountUser());
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

    /**
     * @param AccountUser $accountUser
     * @return array|RedirectResponse
     */
    protected function update(AccountUser $accountUser)
    {
        $form = $this->createForm(AccountUserType::NAME, $accountUser);
        $handler = new FrontendAccountUserHandler(
            $form,
            $this->getRequest(),
            $this->get('orob2b_account_user.manager')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            'orob2b_customer_account_user_security_login',
            'orob2b_customer_account_user_security_login',
            $this->get('translator')->trans('orob2b.customer.controller.accountuser.registered.message'),
            $handler
        );

        return $result;
    }
}
