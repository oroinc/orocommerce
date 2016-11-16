<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountUserAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_update';

    /**
     * Get account user address form view
     *
     * @param AccountUserAddress $accountUserAddress
     * @param AccountUser        $accountUser
     *
     * @return FormView
     */
    public function getAddressFormView(AccountUserAddress $accountUserAddress, AccountUser $accountUser)
    {
        $options = $this->getFormOptions($accountUserAddress, $accountUser);

        return $this->getFormView(AccountUserTypedAddressType::NAME, $accountUserAddress, $options);
    }

    /**
     * Get account user address form
     *
     * @param AccountUserAddress $accountUserAddress
     * @param AccountUser        $accountUser
     *
     * @return FormInterface
     */
    public function getAddressForm(AccountUserAddress $accountUserAddress, AccountUser $accountUser)
    {
        $options = $this->getFormOptions($accountUserAddress, $accountUser);

        return $this->getForm(AccountUserTypedAddressType::NAME, $accountUserAddress, $options);
    }

    /**
     * @param AccountUserAddress $accountUserAddress
     * @param AccountUser        $accountUser
     *
     * @return array
     */
    private function getFormOptions(AccountUserAddress $accountUserAddress, AccountUser $accountUser)
    {
        $options = [];
        if ($accountUserAddress->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => $accountUserAddress->getId(), 'entityId' => $accountUser->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
                ['entityId' => $accountUser->getId()]
            );
        }

        return $options;
    }
}
