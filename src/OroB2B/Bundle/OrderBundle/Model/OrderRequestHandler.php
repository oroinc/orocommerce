<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;

class OrderRequestHandler
{
    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountUserClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $accountClass
     * @param string $accountUserClass
     */
    public function __construct(ManagerRegistry $registry, $accountClass, $accountUserClass)
    {
        $this->registry = $registry;
        $this->accountClass = $accountClass;
        $this->accountUserClass = $accountUserClass;
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
        if (!$this->request) {
            return $default;
        }

        $request = $this->request->get(OrderType::NAME);
        if (!is_array($request) || !array_key_exists($var, $request)) {
            return $default;
        } else {
            return $request[$var];
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

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
