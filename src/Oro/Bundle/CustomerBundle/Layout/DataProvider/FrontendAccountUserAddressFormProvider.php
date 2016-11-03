<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserTypedAddressType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountUserAddressFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_user_address_update';

    /**
     * Get form accessor with account user address form
     *
     * @param AccountUserAddress $accountUserAddress
     * @param AccountUser        $accountUser
     *
     * @return FormInterface
     */
    public function getAddressForm(
        AccountUserAddress $accountUserAddress,
        AccountUser $accountUser
    ) {
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

        return $this->getForm(AccountUserTypedAddressType::NAME, $accountUserAddress, $options);
    }
}
