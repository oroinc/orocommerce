<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendCustomerAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_customer_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_customer_address_update';

    /**
     * Get customer address form view
     *
     * @param CustomerAddress $customerAddress
     * @param Customer        $customer
     *
     * @return FormView
     */
    public function getAddressFormView(CustomerAddress $customerAddress, Customer $customer)
    {
        $options = $this->getFormOptions($customerAddress, $customer);

        return $this->getFormView(FrontendCustomerTypedAddressType::NAME, $customerAddress, $options);
    }

    /**
     * Get customer address form
     *
     * @param CustomerAddress $customerAddress
     * @param Customer        $customer
     *
     * @return FormInterface
     */
    public function getAddressForm(CustomerAddress $customerAddress, Customer $customer)
    {
        $options = $this->getFormOptions($customerAddress, $customer);

        return $this->getForm(FrontendCustomerTypedAddressType::NAME, $customerAddress, $options);
    }

    /**
     * @param CustomerAddress $customerAddress
     * @param Customer        $customer
     *
     * @return array
     */
    private function getFormOptions(CustomerAddress $customerAddress, Customer $customer)
    {
        $options = [];
        if ($customerAddress->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => $customerAddress->getId(), 'entityId' => $customer->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_ADDRESS_CREATE_ROUTE_NAME,
                ['entityId' => $customer->getId()]
            );
        }

        return $options;
    }
}
