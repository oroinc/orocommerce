<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_address_update';

    /**
     * Get account address form view
     *
     * @param CustomerAddress $accountAddress
     * @param Customer        $account
     *
     * @return FormView
     */
    public function getAddressFormView(CustomerAddress $accountAddress, Customer $account)
    {
        $options = $this->getFormOptions($accountAddress, $account);

        return $this->getFormView(FrontendAccountTypedAddressType::NAME, $accountAddress, $options);
    }

    /**
     * Get account address form
     *
     * @param CustomerAddress $accountAddress
     * @param Customer        $account
     *
     * @return FormInterface
     */
    public function getAddressForm(CustomerAddress $accountAddress, Customer $account)
    {
        $options = $this->getFormOptions($accountAddress, $account);

        return $this->getForm(FrontendAccountTypedAddressType::NAME, $accountAddress, $options);
    }

    /**
     * @param CustomerAddress $accountAddress
     * @param Customer        $account
     *
     * @return array
     */
    private function getFormOptions(CustomerAddress $accountAddress, Customer $account)
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
