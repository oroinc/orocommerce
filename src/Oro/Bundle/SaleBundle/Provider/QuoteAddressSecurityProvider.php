<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\SaleBundle\Entity\Quote;

class QuoteAddressSecurityProvider
{
    const MANUAL_EDIT_ACTION = 'orob2b_quote_address_%s_allow_manual';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var QuoteAddressProvider */
    protected $QuoteAddressProvider;

    /** @var string */
    protected $accountAddressClass;

    /** @var string */
    protected $accountUserAddressClass;

    /**
     * @param SecurityFacade $securityFacade
     * @param QuoteAddressProvider $quoteAddressProvider
     * @param string $accountAddressClass
     * @param string $accountUserAddressClass
     */
    public function __construct(
        SecurityFacade $securityFacade,
        QuoteAddressProvider $quoteAddressProvider,
        $accountAddressClass,
        $accountUserAddressClass
    ) {
        $this->securityFacade = $securityFacade;
        $this->QuoteAddressProvider = $quoteAddressProvider;
        $this->accountAddressClass = $accountAddressClass;
        $this->accountUserAddressClass = $accountUserAddressClass;
    }

    /**
     * @param Quote $quote
     * @param string $type
     *
     * @return bool
     */
    public function isAddressGranted(Quote $quote, $type)
    {
        return $this->isAccountAddressGranted($type, $quote->getAccount()) ||
            $this->isAccountUserAddressGranted($type, $quote->getAccountUser());
    }

    /**
     * @param string $type
     * @param Account $account
     *
     * @return bool
     */
    public function isAccountAddressGranted($type, Account $account = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->securityFacade->isGranted(
            $this->getClassPermission('VIEW', $this->accountAddressClass)
        );

        if (!$hasPermissions) {
            return false;
        }

        if (!$account) {
            return false;
        }

        return (bool)$this->QuoteAddressProvider->getAccountAddresses($account, $type);
    }

    /**
     * @param string $type
     * @param AccountUser $accountUser
     *
     * @return bool
     */
    public function isAccountUserAddressGranted($type, AccountUser $accountUser = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->securityFacade
                ->isGranted($this->getClassPermission('VIEW', $this->accountUserAddressClass))
            && $this->securityFacade->isGranted($this->getTypedPermission($type));

        if (!$hasPermissions) {
            return false;
        }

        if (!$accountUser) {
            return false;
        }

        return (bool)$this->QuoteAddressProvider->getAccountUserAddresses($accountUser, $type);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getTypedPermission($type)
    {
        QuoteAddressProvider::assertType($type);

        return $this->getPermission(QuoteAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY);
    }

    /**
     * @param string $permission
     * @param string $className
     *
     * @return string
     */
    protected function getClassPermission($permission, $className)
    {
        return sprintf('%s;entity:%s', $permission, $className);
    }

    /**
     * @param string $permission
     *
     * @return string
     */
    protected function getPermission($permission)
    {
        if (!$this->securityFacade->getLoggedUser() instanceof AccountUser) {
            $permission .= QuoteAddressProvider::ADMIN_ACL_POSTFIX;
        }

        return $permission;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isManualEditGranted($type)
    {
        QuoteAddressProvider::assertType($type);

        $permission = sprintf(self::MANUAL_EDIT_ACTION, $type);

        return $this->securityFacade->isGranted($this->getPermission($permission));
    }
}
