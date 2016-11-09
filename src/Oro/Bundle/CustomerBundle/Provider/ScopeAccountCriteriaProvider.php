<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeAccountCriteriaProvider extends AbstractScopeCriteriaProvider
{
        const ACCOUNT = 'account';

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
                if (null !== $loggedUser && $loggedUser instanceof AccountUser) {
                        return [self::ACCOUNT => $loggedUser->getAccount()];
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
         * @return string
         */
        protected function getCriteriaValueType()
        {
                return Account::class;
        }
}
