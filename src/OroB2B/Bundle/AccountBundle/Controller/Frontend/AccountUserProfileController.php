<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;
use OroB2B\Bundle\AccountBundle\Form\Handler\FrontendAccountUserHandler;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserProfileType;

class AccountUserProfileController extends Controller
{
    /**
     * Create account user form
     *
     * @Route("/register", name="orob2b_account_frontend_account_user_register")
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:register.html.twig")
     * @Layout()
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_profile'));
        }
        $this->checkPermissions();

        return $this->handleForm($request);
    }

    protected function checkPermissions()
    {
        $isRegistrationAllowed = $this->get('oro_config.manager')->get('oro_b2b_account.registration_allowed');
        if (!$isRegistrationAllowed) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = new AccountUser();

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_config.manager');
        $defaultOwnerId = $configManager->get('oro_b2b_account.default_account_owner');
        /** @var UserManager $userManager */
        $userManager = $this->get('oro_user.manager');
        /** @var WebsiteManager $websiteManager */
        $websiteManager = $this->get('orob2b_website.manager');
        $website = $websiteManager->getCurrentWebsite();
        /** @var Organization|OrganizationInterface $websiteOrganization */
        $websiteOrganization = $website->getOrganization();

        if (!$websiteOrganization) {
            throw new \RuntimeException('Website organization is empty');
        }

        $defaultRole = $this->getDoctrine()
            ->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
            ->getRepository('OroB2BAccountBundle:AccountUserRole')
            ->getDefaultAccountUserRoleByWebsite($website);

        if (!$defaultRole) {
            throw new \RuntimeException(sprintf('Role "%s" was not found', AccountUser::ROLE_DEFAULT));
        }

        if (!$defaultOwnerId) {
            throw new \RuntimeException('Application Owner is empty');
        }

        /** @var User $owner */
        $owner = $userManager->getRepository()->find($defaultOwnerId);

        $accountUser
            ->setOwner($owner)
            ->addOrganization($websiteOrganization)
            ->setOrganization($websiteOrganization)
            ->addRole($defaultRole);

        return $accountUser;
    }

    /**
     * @param AccountUser $accountUser
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function handleForm(Request $request)
    {
        $context = new LayoutContext();
        /** @var AccountUser $accountUser */
        $accountUser = $this->get('orob2b_account.layout.data_provider.new_account_user')->getData($context);
        /** @var AccountUserManager $userManager  */
        $userManager = $this->get('orob2b_account_user.manager');

        /** @var FormInterface $form */
        $form = $this->get('orob2b_account.form.frontend_account_registration');
        $form->setData($accountUser);
        $handler = new FrontendAccountUserHandler($form, $request, $userManager);

        if ($userManager->isConfirmationRequired()) {
            $registrationMessage = 'orob2b.account.controller.accountuser.registered_with_confirmation.message';
        } else {
            $registrationMessage = 'orob2b.account.controller.accountuser.registered.message';
        }
        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'orob2b_account_account_user_security_login'],
            ['route' => 'orob2b_account_account_user_security_login'],
            $this->get('translator')->trans($registrationMessage),
            $handler
        );
        if ($response instanceof Response) {
            return $response;
        }
        return $context;
    }

    /**
     * @Route("/confirm-email", name="orob2b_account_frontend_account_user_confirmation")
     * @param Request $request
     * @return RedirectResponse
     */
    public function confirmEmailAction(Request $request)
    {
        $userManager = $this->get('orob2b_account_user.manager');
        /** @var AccountUser $accountUser */
        $accountUser = $userManager->findUserByUsernameOrEmail($request->get('username'));
        $token = $request->get('token');
        if ($accountUser === null || empty($token) || $accountUser->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('orob2b.account.controller.accountuser.confirmation_error.message')
            );
        }

        $messageType = 'warn';
        $message = 'orob2b.account.controller.accountuser.already_confirmed.message';
        if (!$accountUser->isConfirmed()) {
            $userManager->confirmRegistration($accountUser);
            $userManager->updateUser($accountUser);
            $messageType = 'success';
            $message = 'orob2b.account.controller.accountuser.confirmed.message';
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);
        return $this->redirect($this->generateUrl('orob2b_account_account_user_security_login'));
    }

    /**
     * @Route("/profile", name="orob2b_account_frontend_account_user_profile")
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:viewProfile.html.twig")
     * @AclAncestor("orob2b_account_frontend_account_user_view")
     *
     * @return array
     */
    public function profileAction()
    {
        return [
            'entity' => $this->getUser(),
            'editRoute' => 'orob2b_account_frontend_account_user_profile_update'
        ];
    }

    /**
     * Edit account user form
     *
     * @Route("/profile/update", name="orob2b_account_frontend_account_user_profile_update")
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:updateProfile.html.twig")
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $accountUser = $this->getUser();
        $form = $this->createForm(FrontendAccountUserProfileType::NAME, $accountUser);
        $handler = new FrontendAccountUserHandler(
            $form,
            $request,
            $this->get('orob2b_account_user.manager')
        );
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'orob2b_account_frontend_account_user_profile_update'],
            ['route' => 'orob2b_account_frontend_account_user_profile'],
            $this->get('translator')->trans('orob2b.account.controller.accountuser.profile_updated.message'),
            $handler
        );
    }
}
