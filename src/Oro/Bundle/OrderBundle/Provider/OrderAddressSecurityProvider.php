<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provide access availability decision for customer and customer user addresses for given order.
 */
class OrderAddressSecurityProvider
{
    private const MANUAL_EDIT_ACTION = 'oro_order_address_%s_allow_manual';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var FrontendHelper */
    private $frontendHelper;

    /** @var OrderAddressProvider */
    private $orderAddressProvider;

    /** @var string */
    private $customerAddressClass;

    /** @var string */
    private $customerUserAddressClass;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FrontendHelper                $frontendHelper
     * @param OrderAddressProvider          $orderAddressProvider
     * @param string                        $customerAddressClass
     * @param string                        $customerUserAddressClass
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FrontendHelper $frontendHelper,
        OrderAddressProvider $orderAddressProvider,
        $customerAddressClass,
        $customerUserAddressClass
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->frontendHelper = $frontendHelper;
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
    private function getTypedPermission($type)
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
    private function getClassPermission($permission, $className)
    {
        return sprintf('%s;entity:%s', $permission, $className);
    }

    /**
     * @param string $permission
     * @return string
     */
    private function getPermission($permission)
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
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
