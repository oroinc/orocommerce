<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountAddressRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserAddressRepository;

class OrderAddressProvider
{
    const ADDRESS_TYPE_SHIPPING = 'shipping';
    const ADDRESS_TYPE_BILLING = 'billing';

    const ADMIN_ACL_POSTFIX = '_backend';

    const ACCOUNT_ADDRESS_ANY = 'account_any';
    const ACCOUNT_USER_ADDRESS_DEFAULT = 'account_user_default';
    const ACCOUNT_USER_ADDRESS_ANY = 'account_user_any';

    const ADDRESS_SHIPPING_ACCOUNT_USE_ANY = 'orob2b_order_address_shipping_account_use_any';
    const ADDRESS_SHIPPING_ACCOUNT_USER_USE_DEFAULT = 'orob2b_order_address_shipping_account_user_use_default';
    const ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY = 'orob2b_order_address_shipping_account_user_use_any';

    const ADDRESS_BILLING_ACCOUNT_USE_ANY = 'orob2b_order_address_billing_account_use_any';
    const ADDRESS_BILLING_ACCOUNT_USER_USE_DEFAULT = 'orob2b_order_address_billing_account_user_use_default';
    const ADDRESS_BILLING_ACCOUNT_USER_USE_ANY = 'orob2b_order_address_billing_account_user_use_any';

    /**
     * @var array
     */
    protected $permissionsByType = [
        self::ADDRESS_TYPE_SHIPPING => [
            self::ACCOUNT_ADDRESS_ANY => self::ADDRESS_SHIPPING_ACCOUNT_USE_ANY,
            self::ACCOUNT_USER_ADDRESS_DEFAULT => self::ADDRESS_SHIPPING_ACCOUNT_USER_USE_DEFAULT,
            self::ACCOUNT_USER_ADDRESS_ANY => self::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY,
        ],
        self::ADDRESS_TYPE_BILLING => [
            self::ACCOUNT_ADDRESS_ANY => self::ADDRESS_BILLING_ACCOUNT_USE_ANY,
            self::ACCOUNT_USER_ADDRESS_DEFAULT => self::ADDRESS_BILLING_ACCOUNT_USER_USE_DEFAULT,
            self::ACCOUNT_USER_ADDRESS_ANY => self::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY,
        ],
    ];

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var string
     */
    protected $accountAddressClass;

    /**
     * @var string
     */
    protected $accountUserAddressClass;

    /**
     * @param SecurityFacade $securityFacade
     * @param ManagerRegistry $registry
     * @param AclHelper $aclHelper
     * @param string $accountAddressClass
     * @param string $accountUserAddressClass
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ManagerRegistry $registry,
        AclHelper $aclHelper,
        $accountAddressClass,
        $accountUserAddressClass
    ) {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;

        $this->accountAddressClass = $accountAddressClass;
        $this->accountUserAddressClass = $accountUserAddressClass;
    }

    /**
     * @param Account $account
     * @param string $type
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getAccountAddresses(Account $account, $type)
    {
        $this->assertType($type);

        $repository = $this->getAccountAddressRepository();
        if ($this->securityFacade->isGranted($this->getPermission($type, self::ACCOUNT_ADDRESS_ANY))) {
            return $repository->getAddressesByType($account, $type, $this->aclHelper);
        } elseif ($this->securityFacade->isGrantedClassPermission('VIEW', $this->accountAddressClass)) {
            return $repository->getDefaultAddressesByType($account, $type, $this->aclHelper);
        }

        return [];
    }

    /**
     * @param AccountUser $accountUser
     * @param string $type
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getAccountUserAddresses(AccountUser $accountUser, $type)
    {
        $this->assertType($type);

        $repository = $this->getAccountUserAddressRepository();
        if ($this->securityFacade->isGranted($this->getPermission($type, self::ACCOUNT_USER_ADDRESS_ANY))) {
            return $repository->getAddressesByType($accountUser, $type, $this->aclHelper);
        } elseif ($this->securityFacade->isGranted($this->getPermission($type, self::ACCOUNT_USER_ADDRESS_DEFAULT))) {
            return $repository->getDefaultAddressesByType($accountUser, $type, $this->aclHelper);
        }

        return [];
    }

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    protected function assertType($type)
    {
        $supportedTypes = [self::ADDRESS_TYPE_BILLING, self::ADDRESS_TYPE_SHIPPING];
        if (!in_array($type, $supportedTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown type "%s", known types are: %s',
                    $type,
                    implode(', ', $supportedTypes)
                )
            );
        }
    }

    /**
     * @return AccountAddressRepository
     */
    protected function getAccountAddressRepository()
    {
        return $this->registry->getManagerForClass($this->accountAddressClass)
            ->getRepository($this->accountAddressClass);
    }

    /**
     * @return AccountUserAddressRepository
     */
    protected function getAccountUserAddressRepository()
    {
        return $this->registry->getManagerForClass($this->accountUserAddressClass)
            ->getRepository($this->accountUserAddressClass);
    }

    /**
     * @param string $type
     * @param string $key
     * @return string
     */
    protected function getPermission($type, $key)
    {
        $postfix = '';
        if ($this->securityFacade->getLoggedUser() instanceof User) {
            $postfix = self::ADMIN_ACL_POSTFIX;
        }

        return $this->permissionsByType[$type][$key] . $postfix;
    }
}
