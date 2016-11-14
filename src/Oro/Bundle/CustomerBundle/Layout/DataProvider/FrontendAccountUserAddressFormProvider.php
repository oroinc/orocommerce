<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserTypedAddressType;

class FrontendAccountUserAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_update';

    /**
     * Get form accessor with account user address form
     *
     * @param AccountUserAddress $accountUserAddress
     * @param AccountUser $accountUser
     *
     * @return FormAccessor
     */
    public function getAddressForm(AccountUserAddress $accountUserAddress, AccountUser $accountUser)
    {
        if ($accountUserAddress->getId()) {
            return $this->getFormAccessor(
                FrontendAccountUserTypedAddressType::NAME,
                self::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                $accountUserAddress,
                ['id' => $accountUserAddress->getId(), 'entityId' => $accountUser->getId()]
            );
        }

        return $this->getFormAccessor(
            FrontendAccountUserTypedAddressType::NAME,
            self::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
            $accountUserAddress,
            ['entityId' => $accountUser->getId()]
        );
    }
}
