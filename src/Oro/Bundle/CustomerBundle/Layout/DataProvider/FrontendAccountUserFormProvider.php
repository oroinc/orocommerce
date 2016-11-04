<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordRequestType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordResetType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserProfileType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountUserFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_CREATE_ROUTE_NAME            = 'oro_customer_frontend_account_user_create';
    const ACCOUNT_USER_UPDATE_ROUTE_NAME            = 'oro_customer_frontend_account_user_update';
    const ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME    = 'oro_customer_frontend_account_user_profile_update';
    const ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME     = 'oro_customer_frontend_account_user_reset_request';
    const ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME    = 'oro_customer_frontend_account_user_password_reset';

    /**
     * @param AccountUser $accountUser
     *
     * @return FormView
     */
    public function getAccountUserFormView(AccountUser $accountUser)
    {
        $options = $this->getAccountUserFormOptions($accountUser);

        return $this->getFormView(FrontendAccountUserType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormInterface
     */
    public function getAccountUserForm(AccountUser $accountUser)
    {
        $options = $this->getAccountUserFormOptions($accountUser);

        return $this->getForm(FrontendAccountUserType::NAME, $accountUser, $options);
    }

    /**
     * @param array $options
     *
     * @return FormView
     */
    public function getForgotPasswordFormView(array $options = [])
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME);

        return $this->getFormView(AccountUserPasswordRequestType::NAME, null, $options);
    }

    /**
     * @param array $options
     *
     * @return FormInterface
     */
    public function getForgotPasswordForm(array $options = [])
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME);

        return $this->getForm(AccountUserPasswordRequestType::NAME, null, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormView
     */
    public function getResetPasswordFormView(AccountUser $accountUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getFormView(AccountUserPasswordResetType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormInterface
     */
    public function getResetPasswordForm(AccountUser $accountUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getForm(AccountUserPasswordResetType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormView
     */
    public function getProfileFormView(AccountUser $accountUser)
    {
        $options = $this->getProfilerFormOptions($accountUser);

        return $this->getFormView(FrontendAccountUserProfileType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormInterface
     */
    public function getProfileForm(AccountUser $accountUser)
    {
        $options = $this->getProfilerFormOptions($accountUser);

        return $this->getForm(FrontendAccountUserProfileType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    private function getAccountUserFormOptions(AccountUser $accountUser)
    {
        $options = [];

        if ($accountUser->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_UPDATE_ROUTE_NAME,
                ['id' => $accountUser->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_CREATE_ROUTE_NAME
            );
        }

        return $options;
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    private function getProfilerFormOptions(AccountUser $accountUser)
    {
        $options = [];
        if ($accountUser->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME,
                ['id' => $accountUser->getId()]
            );

            return $options;
        }

        throw new \RuntimeException(
            sprintf(
                'Entity with type "%s" must be loaded. Method getId() return NULL.',
                AccountUser::class
            )
        );
    }
}
