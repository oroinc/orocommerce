<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_address_update';

    /**
     * Get account address form view
     *
     * @param AccountAddress $accountAddress
     * @param Account        $account
     *
     * @return FormView
     */
    public function getAddressFormView(AccountAddress $accountAddress, Account $account)
    {
        $options = $this->getFormOptions($accountAddress, $account);

        return $this->getFormView(AccountTypedAddressType::NAME, $accountAddress, $options);
    }

    /**
     * Get account address form
     *
     * @param AccountAddress $accountAddress
     * @param Account        $account
     *
     * @return FormInterface
     */
    public function getAddressForm(AccountAddress $accountAddress, Account $account)
    {
        $options = $this->getFormOptions($accountAddress, $account);

        return $this->getForm(AccountTypedAddressType::NAME, $accountAddress, $options);
    }

    /**
     * @param AccountAddress $accountAddress
     * @param Account        $account
     *
     * @return array
     */
    private function getFormOptions(AccountAddress $accountAddress, Account $account)
    {
        $options = [];
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

        return $options;
    }
}
