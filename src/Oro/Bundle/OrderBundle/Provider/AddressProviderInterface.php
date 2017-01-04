<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;

interface AddressProviderInterface
{
    /**
     * @param Account $account
     * @param string $type
     *
     * @return CustomerAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountAddresses(Account $account, $type);

    /**
     * @param AccountUser $accountUser
     * @param string $type
     *
     * @return CustomerUserAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountUserAddresses(AccountUser $accountUser, $type);

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public static function assertType($type);
}
