<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;

class AccountMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Account $account
     * @return array
     */
    public function createMessage($account)
    {
        $message = [self::ID => null];
        if ($account instanceof Account) {
            $message[self::ID] = $account->getId();
        }
        
        return $message;
    }

    /**
     * @param array|null $data
     * @return Account
     */
    public function getEntityFromMessage($data)
    {
        $account = null;
        if (isset($data[self::ID])) {
            $account = $this->registry->getManagerForClass(Account::class)
                ->getRepository(Account::class)
                ->find($data[self::ID]);
        }
        if (!$account) {
            throw new InvalidArgumentException();
        }

        return $account;
    }
}
