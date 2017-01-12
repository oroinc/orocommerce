<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;

class CustomerMessageFactory implements MessageFactoryInterface
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
     * @param Customer $customer
     * @return array
     */
    public function createMessage($customer)
    {
        $message = [self::ID => null];
        if ($customer instanceof Customer) {
            $message[self::ID] = $customer->getId();
        }

        return $message;
    }

    /**
     * @param array|null $data
     * @return Customer
     */
    public function getEntityFromMessage($data)
    {
        $customer = null;
        if (isset($data[self::ID])) {
            $customer = $this->registry->getManagerForClass(Customer::class)
                ->getRepository(Customer::class)
                ->find($data[self::ID]);
        }
        if (!$customer) {
            throw new InvalidArgumentException();
        }

        return $customer;
    }
}
