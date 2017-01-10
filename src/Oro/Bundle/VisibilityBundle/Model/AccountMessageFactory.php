<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;

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
     * @param Customer $account
     * @return array
     */
    public function createMessage($account)
    {
        $message = [self::ID => null];
        if ($account instanceof Customer) {
            $message[self::ID] = $account->getId();
        }

        return $message;
    }

    /**
     * @param array|null $data
     * @return Customer
     */
    public function getEntityFromMessage($data)
    {
        $account = null;
        if (isset($data[self::ID])) {
            $account = $this->registry->getManagerForClass(Customer::class)
                ->getRepository(Customer::class)
                ->find($data[self::ID]);
        }
        if (!$account) {
            throw new InvalidArgumentException();
        }

        return $account;
    }
}
