<?php

namespace Oro\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\AccountBundle\Form\Type\AccountUserTypedAddressType;

class FrontendAccountUserAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_address_update';

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
                AccountUserTypedAddressType::NAME,
                self::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                $accountUserAddress,
                ['id' => $accountUserAddress->getId(), 'entityId' => $accountUser->getId()]
            );
        }

        return $this->getFormAccessor(
            AccountUserTypedAddressType::NAME,
            self::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
            $accountUserAddress,
            ['entityId' => $accountUser->getId()]
        );
    }
}
