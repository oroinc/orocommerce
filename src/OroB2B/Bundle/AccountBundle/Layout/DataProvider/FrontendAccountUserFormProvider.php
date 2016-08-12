<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserPasswordRequestType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserPasswordResetType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserProfileType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;

class FrontendAccountUserFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_CREATE_ROUTE_NAME            = 'orob2b_account_frontend_account_user_create';
    const ACCOUNT_USER_UPDATE_ROUTE_NAME            = 'orob2b_account_frontend_account_user_update';
    const ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME    = 'orob2b_account_frontend_account_user_profile_update';
    const ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME     = 'orob2b_account_frontend_account_user_reset_request';
    const ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME    = 'orob2b_account_frontend_account_user_password_reset';

    /**
     * @param AccountUser $accountUser
     *
     * @return FormAccessor
     */
    public function getAccountUserForm(AccountUser $accountUser)
    {
        if ($accountUser->getId()) {
            return $this->getFormAccessor(
                FrontendAccountUserType::NAME,
                self::ACCOUNT_USER_UPDATE_ROUTE_NAME,
                $accountUser,
                ['id' => $accountUser->getId()]
            );
        }

        return $this->getFormAccessor(
            FrontendAccountUserType::NAME,
            self::ACCOUNT_USER_CREATE_ROUTE_NAME,
            $accountUser
        );
    }

    /**
     * @return FormAccessor
     */
    public function getForgotPasswordForm()
    {
        return $this->getFormAccessor(
            AccountUserPasswordRequestType::NAME,
            self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME
        );
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormAccessor
     */
    public function getResetPasswordForm(AccountUser $accountUser = null)
    {
        return $this->getFormAccessor(
            AccountUserPasswordResetType::NAME,
            self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME,
            $accountUser
        );
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormAccessor
     */
    public function getProfileForm(AccountUser $accountUser)
    {
        if ($accountUser->getId()) {
            return $this->getFormAccessor(
                FrontendAccountUserProfileType::NAME,
                self::ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME,
                $accountUser,
                ['id' => $accountUser->getId()]
            );
        }

        throw new \RuntimeException(
            sprintf(
                'Entity with type "%s" must be loaded. Method getId() return NULL.',
                AccountUser::class,
                gettype($accountUser)
            )
        );
    }
}
