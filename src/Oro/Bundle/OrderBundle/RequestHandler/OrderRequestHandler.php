<?php

namespace Oro\Bundle\OrderBundle\RequestHandler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extracts order-related data from HTTP requests.
 *
 * Provides methods to retrieve customer and customer user information from the current HTTP request,
 * specifically from order form data. Handles entity lookup using the Doctrine registry to resolve entity IDs
 * to actual objects.
 */
class OrderRequestHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $customerClass;

    /** @var string */
    protected $customerUserClass;

    /**
     * @param ManagerRegistry $registry
     * @param RequestStack $requestStack
     * @param string $customerClass
     * @param string $customerUserClass
     */
    public function __construct(
        ManagerRegistry $registry,
        RequestStack $requestStack,
        $customerClass,
        $customerUserClass
    ) {
        $this->registry = $registry;
        $this->customerClass = $customerClass;
        $this->customerUserClass = $customerUserClass;
        $this->requestStack = $requestStack;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer()
    {
        $customerId = $this->getFromRequest('customer');
        $customer = null;
        if ($customerId) {
            $customer = $this->findEntity($this->customerClass, $customerId);
        }

        return $customer;
    }

    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser()
    {
        $customerUserId = $this->getFromRequest('customerUser');
        $customerUser = null;
        if ($customerUserId) {
            $customerUser = $this->findEntity($this->customerUserClass, $customerUserId);
        }

        return $customerUser;
    }

    /**
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    protected function getFromRequest($var, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $default;
        }

        $orderType = $request->get(OrderType::NAME);
        if (!is_array($orderType) || !array_key_exists($var, $orderType)) {
            return $default;
        } else {
            return $orderType[$var];
        }
    }

    /**
     * @param string $entityClass
     * @param int $id
     * @return object
     */
    protected function findEntity($entityClass, $id)
    {
        return $this->registry->getManagerForClass($entityClass)->find($entityClass, $id);
    }
}
