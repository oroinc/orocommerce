<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountTypedAddressType;

class FrontendAccountAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'oro_account_frontend_account_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'oro_account_frontend_account_address_update';

    /**
     * Get form accessor with account address form
     *
     * @param AccountAddress $accountAddress
     * @param Account $account
     *
     * @return FormAccessor
     */
    public function getAddressForm(AccountAddress $accountAddress, Account $account)
    {
        if ($accountAddress->getId()) {
            return $this->getFormAccessor(
                AccountTypedAddressType::NAME,
                self::ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME,
                $accountAddress,
                ['id' => $accountAddress->getId(), 'entityId' => $account->getId()]
            );
        }

        return $this->getFormAccessor(
            AccountTypedAddressType::NAME,
            self::ACCOUNT_ADDRESS_CREATE_ROUTE_NAME,
            $accountAddress,
            ['entityId' => $account->getId()]
        );
    }
}
