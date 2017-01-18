<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserPasswordRequestType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserPasswordResetType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserProfileType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendOwnerSelectType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FrontendCustomerUserFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_CREATE_ROUTE_NAME            = 'oro_customer_frontend_customer_user_create';
    const ACCOUNT_USER_UPDATE_ROUTE_NAME            = 'oro_customer_frontend_customer_user_update';
    const ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME    = 'oro_customer_frontend_customer_user_profile_update';
    const ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME     = 'oro_customer_frontend_customer_user_reset_request';
    const ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME    = 'oro_customer_frontend_customer_user_password_reset';

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormView
     */
    public function getCustomerUserFormView(CustomerUser $customerUser)
    {
        $options = $this->getCustomerUserFormOptions($customerUser);

        return $this->getFormView(FrontendCustomerUserType::NAME, $customerUser, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormInterface
     */
    public function getCustomerUserForm(CustomerUser $customerUser)
    {
        $options = $this->getCustomerUserFormOptions($customerUser);

        return $this->getForm(FrontendCustomerUserType::NAME, $customerUser, $options);
    }

    /**
     * @param array $options
     *
     * @return FormView
     */
    public function getForgotPasswordFormView(array $options = [])
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME);

        return $this->getFormView(CustomerUserPasswordRequestType::NAME, null, $options);
    }

    /**
     * @param array $options
     *
     * @return FormInterface
     */
    public function getForgotPasswordForm(array $options = [])
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_RESET_REQUEST_ROUTE_NAME);

        return $this->getForm(CustomerUserPasswordRequestType::NAME, null, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormView
     */
    public function getResetPasswordFormView(CustomerUser $customerUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getFormView(CustomerUserPasswordResetType::NAME, $customerUser, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormInterface
     */
    public function getResetPasswordForm(CustomerUser $customerUser = null)
    {
        $options['action'] = $this->generateUrl(self::ACCOUNT_USER_PASSWORD_RESET_ROUTE_NAME);

        return $this->getForm(CustomerUserPasswordResetType::NAME, $customerUser, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormView
     */
    public function getProfileFormView(CustomerUser $customerUser)
    {
        $options = $this->getProfilerFormOptions($customerUser);

        return $this->getFormView(FrontendCustomerUserProfileType::NAME, $customerUser, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return FormInterface
     */
    public function getProfileForm(CustomerUser $customerUser)
    {
        $options = $this->getProfilerFormOptions($customerUser);

        return $this->getForm(FrontendCustomerUserProfileType::NAME, $customerUser, $options);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return array
     */
    private function getCustomerUserFormOptions(CustomerUser $customerUser)
    {
        $options = [];

        if ($customerUser->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_UPDATE_ROUTE_NAME,
                ['id' => $customerUser->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_CREATE_ROUTE_NAME
            );
        }

        return $options;
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return array
     */
    private function getProfilerFormOptions(CustomerUser $customerUser)
    {
        $options = [];
        if ($customerUser->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_PROFILE_UPDATE_ROUTE_NAME,
                ['id' => $customerUser->getId()]
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
     * @param CustomerUser $customerUser
     * @param object $target
     * @return FormInterface
     */
    public function getCustomerUserSelectFormView(CustomerUser $customerUser, $target)
    {
        return $this->getFormView(
            FrontendOwnerSelectType::NAME,
            $customerUser,
            ['targetObject' => $target]
        );
    }
}
