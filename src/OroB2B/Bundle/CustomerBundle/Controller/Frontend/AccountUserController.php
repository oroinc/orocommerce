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
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\CustomerBundle\Form\Type\FrontendAccountUserRegistrationType;

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

        /** @var WebsiteManager $websiteManager */
        $websiteManager = $this->get('orob2b_website.manager');
        $website = $websiteManager->getCurrentWebsite();
        $websiteOrganization = $website->getOrganization();

        $defaultRole = $this->getDoctrine()
            ->getManagerForClass('OroB2BCustomerBundle:AccountUserRole')
            ->getRepository('OroB2BCustomerBundle:AccountUserRole')
            ->findOneBy(['role' => $user->getDefaultRole()]);

        $user
            ->addOrganization($websiteOrganization)
            ->setOrganization($websiteOrganization)
            ->addRole($defaultRole);

        return $this->update($user);
    }

    /**
     * @Route("/profile", name="orob2b_customer_frontend_account_user_profile")
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend:view.html.twig")
     * @return array
     */
    public function profileAction()
    {
        return [
            'entity' => $this->getUser(),
            'editRoute' => 'orob2b_customer_frontend_account_user_profile_update'
        ];
    }

    /**
     * Edit account user form
     *
     * @Route("/profile/update", name="orob2b_customer_frontend_account_user_profile_update")
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend:update.html.twig")
     * @return array|RedirectResponse
     */
    public function updateAction()
    {
        return $this->updateProfile($this->getUser());
    }

    /**
     * @param AccountUser $accountUser
     * @return array|RedirectResponse
     */
    protected function updateProfile(AccountUser $accountUser)
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
            ['route' => 'orob2b_customer_frontend_account_user_profile_update'],
            ['route' => 'orob2b_customer_frontend_account_user_profile'],
            $this->get('translator')->trans('orob2b.customer.controller.accountuser.profile_updated.message'),
            $handler
        );

        return $result;
    }

    /**
     * @param AccountUser $accountUser
     * @return array|RedirectResponse
     */
    protected function update(AccountUser $accountUser)
    {
        $form = $this->createForm(FrontendAccountUserRegistrationType::NAME, $accountUser);
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
