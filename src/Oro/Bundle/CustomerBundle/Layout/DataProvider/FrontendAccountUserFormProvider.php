<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordRequestType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordResetType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserProfileType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendOwnerSelectType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FrontendAccountUserFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_CREATE_ROUTE_NAME            = 'oro_customer_frontend_account_user_create';
    const ACCOUNT_USER_UPDATE_ROUTE_NAME            = 'oro_customer_frontend_account_user_update';
    const ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME    = 'oro_customer_frontend_account_user_profile_update';
    const ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME     = 'oro_customer_frontend_account_user_reset_request';
    const ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME    = 'oro_customer_frontend_account_user_password_reset';

    /**
     * @param CustomerUser $accountUser
     *
     * @return FormView
     */
    public function getAccountUserFormView(CustomerUser $accountUser)
    {
        $options = $this->getAccountUserFormOptions($accountUser);

        return $this->getFormView(FrontendAccountUserType::NAME, $accountUser, $options);
    }

    /**
     * @param CustomerUser $accountUser
     *
     * @return FormInterface
     */
    public function getAccountUserForm(CustomerUser $accountUser)
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
     * @param CustomerUser $accountUser
     *
     * @return FormView
     */
    public function getResetPasswordFormView(CustomerUser $accountUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getFormView(AccountUserPasswordResetType::NAME, $accountUser, $options);
    }

    /**
     * @param CustomerUser $accountUser
     *
     * @return FormInterface
     */
    public function getResetPasswordForm(CustomerUser $accountUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getForm(AccountUserPasswordResetType::NAME, $accountUser, $options);
    }

    /**
     * @param CustomerUser $accountUser
     *
     * @return FormView
     */
    public function getProfileFormView(CustomerUser $accountUser)
    {
        $options = $this->getProfilerFormOptions($accountUser);

        return $this->getFormView(FrontendAccountUserProfileType::NAME, $accountUser, $options);
    }

    /**
     * @param CustomerUser $accountUser
     *
     * @return FormInterface
     */
    public function getProfileForm(CustomerUser $accountUser)
    {
        $options = $this->getProfilerFormOptions($accountUser);

        return $this->getForm(FrontendAccountUserProfileType::NAME, $accountUser, $options);
    }

    /**
     * @param CustomerUser $accountUser
     *
     * @return array
     */
    private function getAccountUserFormOptions(CustomerUser $accountUser)
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
     * @param CustomerUser $accountUser
     *
     * @return array
     */
    private function getProfilerFormOptions(CustomerUser $accountUser)
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
                CustomerUser::class
            )
        );
    }

    /**
     * @param CustomerUser $accountUser
     * @param object $target
     * @return FormInterface
     */
    public function getAccountUserSelectFormView(CustomerUser $accountUser, $target)
    {
        return $this->getFormView(
            FrontendOwnerSelectType::NAME,
            $accountUser,
            ['targetObject' => $target]
        );
    }
}
