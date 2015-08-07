<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

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
     * @return bool
     */
    public function isShippingAddressGranted()
    {
        return $this->isShippingAccountAddressGranted() || $this->isShippingAccountUserAddressGranted();
    }

    /**
     * @return bool
     */
    public function isShippingAccountAddressGranted()
    {
        return $this->securityFacade->isGrantedClassPermission('VIEW', $this->accountAddressClass);
    }

    /**
     * @return bool
     */
    public function isShippingAccountUserAddressGranted()
    {
        return $this->securityFacade->isGrantedClassPermission('VIEW', $this->accountUserAddressClass) &&
        $this->securityFacade->isGrantedClassPermission(
            OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY,
            $this->accountUserAddressClass
        );
    }
}
