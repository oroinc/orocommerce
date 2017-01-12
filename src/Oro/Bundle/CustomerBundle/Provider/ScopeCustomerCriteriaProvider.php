<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeCustomerCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const ACCOUNT = 'customer';
    
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;
    
    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }
    
    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }
        $loggedUser = $token->getUser();
        if (null !== $loggedUser && $loggedUser instanceof CustomerUser) {
            return [self::ACCOUNT => $loggedUser->getCustomer()];
        }
    
        return [];
    }
    
    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return static::ACCOUNT;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return Customer::class;
    }
}
