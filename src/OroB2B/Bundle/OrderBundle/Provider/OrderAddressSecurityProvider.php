<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OrderAddressSecurityProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $accountAddressClass;

    /** @var string */
    protected $accountUserAddressClass;

    /**
     * @param SecurityFacade $securityFacade
     * @param string $accountAddressClass
     * @param string $accountUserAddressClass
     */
    public function __construct(SecurityFacade $securityFacade, $accountAddressClass, $accountUserAddressClass)
    {
        $this->securityFacade = $securityFacade;
        $this->accountAddressClass = $accountAddressClass;
        $this->accountUserAddressClass = $accountUserAddressClass;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isAddressGranted($type)
    {
        return $this->isAccountAddressGranted() || $this->isAccountUserAddressGranted($type);
    }

    /**
     * @return bool
     */
    public function isAccountAddressGranted()
    {
        return $this->securityFacade->isGrantedClassPermission('VIEW', $this->accountAddressClass);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isAccountUserAddressGranted($type)
    {
        return $this->securityFacade->isGrantedClassPermission('VIEW', $this->accountUserAddressClass) &&
        $this->securityFacade->isGrantedClassPermission(
            $this->getTypedPermission($type),
            $this->accountUserAddressClass
        );
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getTypedPermission($type)
    {
        if ($type === AddressType::TYPE_SHIPPING) {
            return OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY;
        }

        if ($type === AddressType::TYPE_BILLING) {
            return OrderAddressProvider::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY;
        }

        throw new \InvalidArgumentException(
            sprintf('Expected "%s" or "%s", "%s" given', AddressType::TYPE_SHIPPING, AddressType::TYPE_BILLING, $type)
        );
    }
}
