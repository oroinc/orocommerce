<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

interface AddressProviderInterface
{
    /**
     * @param Account $account
     * @param string $type
     *
     * @return AccountAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountAddresses(Account $account, $type);

    /**
     * @param AccountUser $accountUser
     * @param string $type
     *
     * @return AccountUserAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountUserAddresses(AccountUser $accountUser, $type);

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public static function assertType($type);
}
