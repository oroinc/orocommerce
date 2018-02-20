<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OrderAddressSecurityProvider
{
    const MANUAL_EDIT_ACTION = 'oro_order_address_%s_allow_manual';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OrderAddressProvider */
    protected $orderAddressProvider;

    /** @var string */
    protected $customerAddressClass;

    /** @var string */
    protected $customerUserAddressClass;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param OrderAddressProvider          $orderAddressProvider
     * @param string                        $customerAddressClass
     * @param string                        $customerUserAddressClass
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OrderAddressProvider $orderAddressProvider,
        $customerAddressClass,
        $customerUserAddressClass
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->orderAddressProvider = $orderAddressProvider;
        $this->customerAddressClass = $customerAddressClass;
        $this->customerUserAddressClass = $customerUserAddressClass;
    }

    /**
     * @param Order $order
     * @param string $type
     *
     * @return bool
     */
    public function isAddressGranted(Order $order, $type)
    {
        return $this->isCustomerAddressGranted($type, $order->getCustomer()) ||
            $this->isCustomerUserAddressGranted($type, $order->getCustomerUser());
    }

    /**
     * @param string $type
     * @param Customer $customer
     *
     * @return bool
     */
    public function isCustomerAddressGranted($type, Customer $customer = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->authorizationChecker->isGranted(
            $this->getClassPermission('VIEW', $this->customerAddressClass)
        );

        if (!$hasPermissions) {
            return false;
        }

        if (!$customer) {
            return false;
        }

        return (bool)$this->orderAddressProvider->getCustomerAddresses($customer, $type);
    }

    /**
     * @param string $type
     * @param CustomerUser $customerUser
     *
     * @return bool
     */
    public function isCustomerUserAddressGranted($type, CustomerUser $customerUser = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->authorizationChecker
                ->isGranted($this->getClassPermission('VIEW', $this->customerUserAddressClass))
            && $this->authorizationChecker->isGranted($this->getTypedPermission($type));

        if (!$hasPermissions) {
            return false;
        }

        if (!$customerUser) {
            return false;
        }

        return (bool)$this->orderAddressProvider->getCustomerUserAddresses($customerUser, $type);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getTypedPermission($type)
    {
        OrderAddressProvider::assertType($type);

        if ($type === AddressType::TYPE_SHIPPING) {
            return $this->getPermission(OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY);
        }

        return $this->getPermission(OrderAddressProvider::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY);
    }

    /**
     * @param string $permission
     * @param string $className
     * @return string
     */
    protected function getClassPermission($permission, $className)
    {
        return sprintf('%s;entity:%s', $permission, $className);
    }

    /**
     * @param string $permission
     * @return string
     */
    protected function getPermission($permission)
    {
        if (!$this->tokenAccessor->getUser() instanceof CustomerUser &&
            !$this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
        ) {
            $permission .= OrderAddressProvider::ADMIN_ACL_POSTFIX;
        }

        return $permission;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isManualEditGranted($type)
    {
        OrderAddressProvider::assertType($type);

        $permission = sprintf(self::MANUAL_EDIT_ACTION, $type);

        return $this->authorizationChecker->isGranted($this->getPermission($permission));
    }
}
