<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

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
     * @param array       $options
     *
     * @return FormInterface
     */
    public function getAccountUserForm(AccountUser $accountUser, array $options = [])
    {
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

        return $this->getForm(FrontendAccountUserType::NAME, $accountUser, $options);
    }

    /**
     * @param array $options
     *
     * @return FormInterface
     */
    public function getForgotPasswordForm($options = [])
    {
        $options['action'] = $this->generateUrl(
            self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME
        );

        return $this->getForm(AccountUserPasswordRequestType::NAME, null, $options);
    }

    /**
     * @param AccountUser $accountUser
     * @param array       $options
     *
     * @return FormInterface
     */
    public function getResetPasswordForm(AccountUser $accountUser = null, array $options = [])
    {
        $options['action'] = $this->generateUrl(
            self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME
        );

        return $this->getForm(AccountUserPasswordResetType::NAME, $accountUser, $options);
    }

    /**
     * @param AccountUser $accountUser
     * @param array       $options
     *
     * @return FormInterface
     */
    public function getProfileForm(AccountUser $accountUser, array $options = [])
    {
        if ($accountUser->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME,
                ['id' => $accountUser->getId()]
            );

            return $this->getForm(FrontendAccountUserProfileType::NAME, $accountUser, $options);
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
