<?php

namespace OroB2B\Bundle\OrderBundle\RequestHandler;

use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;

class OrderRequestHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountUserClass;

    /**
     * @param ManagerRegistry $registry
     * @param RequestStack $requestStack
     * @param string $accountClass
     * @param string $accountUserClass
     */
    public function __construct(ManagerRegistry $registry, RequestStack $requestStack, $accountClass, $accountUserClass)
    {
        $this->registry = $registry;
        $this->accountClass = $accountClass;
        $this->accountUserClass = $accountUserClass;
        $this->requestStack = $requestStack;
    }

    /**
     * @return Account|null
     */
    public function getAccount()
    {
        $accountId = $this->getFromRequest('account');
        $account = null;
        if ($accountId) {
            $account = $this->findEntity($this->accountClass, $accountId);
        }

        return $account;
    }

    /**
     * @return AccountUser|null
     */
    public function getAccountUser()
    {
        $accountUserId = $this->getFromRequest('accountUser');
        $accountUser = null;
        if ($accountUserId) {
            $accountUser = $this->findEntity($this->accountUserClass, $accountUserId);
        }

        return $accountUser;
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
