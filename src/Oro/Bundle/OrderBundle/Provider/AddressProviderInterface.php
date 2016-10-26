<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;

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
