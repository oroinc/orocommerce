<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendCustomerUserAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_customer_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_customer_user_address_update';

    /**
     * Get customer user address form view
     *
     * @param CustomerUserAddress $customerUserAddress
     * @param CustomerUser        $customerUser
     *
     * @return FormView
     */
    public function getAddressFormView(CustomerUserAddress $customerUserAddress, CustomerUser $customerUser)
    {
        $options = $this->getFormOptions($customerUserAddress, $customerUser);

        return $this->getFormView(FrontendCustomerUserTypedAddressType::NAME, $customerUserAddress, $options);
    }

    /**
     * Get customer user address form
     *
     * @param CustomerUserAddress $customerUserAddress
     * @param CustomerUser        $customerUser
     *
     * @return FormInterface
     */
    public function getAddressForm(CustomerUserAddress $customerUserAddress, CustomerUser $customerUser)
    {
        $options = $this->getFormOptions($customerUserAddress, $customerUser);

        return $this->getForm(FrontendCustomerUserTypedAddressType::NAME, $customerUserAddress, $options);
    }

    /**
     * @param CustomerUserAddress $customerUserAddress
     * @param CustomerUser        $customerUser
     *
     * @return array
     */
    private function getFormOptions(CustomerUserAddress $customerUserAddress, CustomerUser $customerUser)
    {
        $options = [];
        if ($customerUserAddress->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => $customerUserAddress->getId(), 'entityId' => $customerUser->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
                ['entityId' => $customerUser->getId()]
            );
        }

        return $options;
    }
}
