<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\FrontendAccountUserType;
use OroB2B\Bundle\CustomerBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * Create account user form
     *
     * @Route("/register", name="orob2b_customer_frontend_account_user_register")
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend:register.html.twig")
     * @return array|RedirectResponse
     */
    public function registerAction()
    {
        $isRegistrationAllowed = $this->get('oro_config.manager')->get('oro_b2b_customer.registration_allowed');
        if (!$isRegistrationAllowed) {
            throw new AccessDeniedException();
        }

        $user = new AccountUser();
        // TODO: Replace this with correct organization BB-632
        $orgs = $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization')->findAll();
        $org = reset($orgs);
        $user->addOrganization($org);
        $user->setOrganization($org);

        return $this->update($user);
    }

    /**
     * @Route("/profile", name="orob2b_customer_frontend_account_user_profile")
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend:register.html.twig")
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
     * Edit account user form
     *
     * @Route("/profile/update", name="orob2b_customer_frontend_account_user_update")
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend:update.html.twig")
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
        $form = $this->createForm(FrontendAccountUserType::NAME, $accountUser);
        $handler = new FrontendAccountUserHandler(
            $form,
            $this->getRequest(),
            $this->get('orob2b_account_user.manager')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'orob2b_customer_account_user_security_login'],
            ['route' => 'orob2b_customer_account_user_security_login'],
            $this->get('translator')->trans('orob2b.customer.controller.accountuser.registered.message'),
            $handler
        );

        return $result;
    }
}
