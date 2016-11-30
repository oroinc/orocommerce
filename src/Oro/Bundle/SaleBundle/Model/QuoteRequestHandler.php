<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\CustomerBundle\Entity\Account;

class QuoteRequestHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $quoteClass;

    /** @var string */
    protected $requestClass;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountUserClass;

    /**
     * @param ManagerRegistry $registry
     * @param RequestStack $requestStack
     * @param $accountClass
     * @param $accountUserClass
     */
    public function __construct(
        ManagerRegistry $registry,
        RequestStack $requestStack,
        $accountClass,
        $accountUserClass
    ) {
        $this->registry = $registry;
        $this->accountClass = $accountClass;
        $this->accountUserClass = $accountUserClass;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
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
     *
     * @return mixed
     */
    protected function getFromRequest($var, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $default;
        }

        $orderType = $request->get(QuoteType::NAME);
        if (!is_array($orderType) || !array_key_exists($var, $orderType)) {
            return $default;
        } else {
            return $orderType[$var];
        }
    }

    /**
     * @param string $entityClass
     * @param int $id
     *
     * @return object
     */
    protected function findEntity($entityClass, $id)
    {
        return $this->registry->getManagerForClass($entityClass)->find($entityClass, $id);
    }
}
