<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_address_update';

    /**
     * Get form accessor with account address form
     *
     * @param AccountAddress $accountAddress
     * @param Account        $account
     * @param array          $options
     *
     * @return FormInterface
     */
    public function getAddressForm(AccountAddress $accountAddress, Account $account, array $options = [])
    {
        if ($accountAddress->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => $accountAddress->getId(), 'entityId' => $account->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_ADDRESS_CREATE_ROUTE_NAME,
                ['entityId' => $account->getId()]
            );
        }

        return $this->getForm(AccountTypedAddressType::NAME, $accountAddress, $options);
    }
}
